<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Nomination;
use App\Models\Nominee;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NominationController extends BaseController
{
    private const DEVICE_ALREADY_NOMINATED_MESSAGE = 'This device has already been used to nominate someone already';

    public function index(Request $request)
    {
        $query = Nomination::with('nominee', 'category', 'nominatedBy', 'evaluatedBy');

        if ($request->evaluation_status) {
            $query->where('evaluation_status', $request->evaluation_status);
        }

        if ($request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        $nominations = $query->paginate(20);

        return $this->paginatedResponse($nominations, 'Nominations retrieved successfully');
    }

    public function store(Request $request)
    {
        $achievementLinks = $this->normalizedAchievementLinks($request);
        $hasAchievementDocuments = $this->hasUploadedAchievementDocuments($request);
        $africanCountries = $this->africanCountryNames();
        $request->merge([
            'achievement_links' => $achievementLinks,
            'profile_image' => $this->normalizeSubmittedUrl($request->input('profile_image')),
        ]);

        $request->validate([
            'nominee_id' => 'nullable|exists:nominees,id|required_without:full_name',
            'full_name' => 'nullable|string|max:255|required_without:nominee_id',
            'bio' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'country' => [
                Rule::requiredIf(fn () => $request->filled('full_name') && ! $request->filled('nominee_id')),
                'nullable',
                'string',
                Rule::in($africanCountries),
            ],
            'profile_image' => 'nullable|url',
            'profile_image_file' => [
                Rule::requiredIf(fn () => $request->filled('full_name')
                    && ! $request->filled('profile_image')
                    && ! $request->hasFile('profile_image_file')),
                'image',
                'max:2048',
            ],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where('is_active', true),
            ],
            'nomination_reason' => 'required|string',
            'achievement_documents' => 'nullable|array|max:5',
            'achievement_documents.*' => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,webp|max:2048',
            'achievement_links' => 'nullable|array|max:5',
            'achievement_links.*' => 'nullable|url|max:2048',
            'device_fingerprint' => 'nullable|string|max:1000',
        ], [
            'achievement_links.*.url' => 'Please enter a valid achievement link, for example https://example.com/story.',
            'profile_image_file.uploaded' => 'The profile image could not be uploaded. Please use a JPG, PNG, or WEBP image under 2 MB.',
            'profile_image_file.image' => 'The profile image must be a JPG, PNG, WEBP, or similar image file.',
            'profile_image_file.max' => 'The profile image must be 2 MB or smaller.',
            'achievement_documents.*.uploaded' => 'One achievement document could not be uploaded. Please use files under 2 MB or paste the evidence as a link.',
            'achievement_documents.*.max' => 'Each achievement document must be 2 MB or smaller. You can paste larger evidence as a link.',
        ]);

        if (! $hasAchievementDocuments && empty($achievementLinks)) {
            throw ValidationException::withMessages([
                'achievement_evidence' => 'Please upload at least one achievement document or provide at least one achievement link.',
            ]);
        }

        $nominator = $request->user();

        if (! $nominator) {
            return $this->errorResponse('Unauthenticated', null, 401);
        }

        $nominatorIp = $request->ip();
        $deviceHash = $this->makeDeviceHash($request);
        $userAgent = (string) $request->userAgent();

        if (Nomination::where('nominated_by', $nominator->id)->exists()) {
            return $this->errorResponse('You have already submitted a nomination', null, 409);
        }

        if ($this->deviceHasAlreadyNominated($nominatorIp, $deviceHash)) {
            return $this->errorResponse(self::DEVICE_ALREADY_NOMINATED_MESSAGE, null, 409);
        }

        $category = Category::findOrFail($request->category_id);
        $profileImage = $this->storeProfileImage($request);
        $achievementDocuments = $this->storeAchievementDocuments($request);

        try {
            $nomination = DB::transaction(function () use ($request, $category, $nominator, $nominatorIp, $deviceHash, $userAgent, $profileImage, $achievementDocuments, $achievementLinks) {
                $nominee = $request->nominee_id
                    ? Nominee::lockForUpdate()->findOrFail($request->nominee_id)
                    : Nominee::create([
                        'full_name' => $request->full_name,
                        'bio' => $request->bio,
                        'email' => $request->email,
                        'phone' => $request->phone,
                        'country' => $request->country,
                        'profile_image' => $profileImage ?: $request->profile_image,
                        'category_id' => $category->id,
                        'status' => 'pending',
                    ]);

                if ($request->filled('email') && blank($nominee->email)) {
                    $nominee->update(['email' => $request->email]);
                }

                if ($profileImage && blank($nominee->profile_image)) {
                    $nominee->update(['profile_image' => $profileImage]);
                }

                if ($request->filled('country') && blank($nominee->country)) {
                    $nominee->update(['country' => $request->country]);
                }

                if ((int) $nominee->category_id !== (int) $category->id) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'Nominee does not belong to the selected category',
                        'errors' => null,
                    ], 422));
                }

                if (Nomination::where('nominated_by', $nominator->id)->exists()) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'You have already submitted a nomination',
                        'errors' => null,
                    ], 409));
                }

                if ($this->deviceHasAlreadyNominated($nominatorIp, $deviceHash)) {
                    abort(response()->json([
                        'success' => false,
                        'message' => self::DEVICE_ALREADY_NOMINATED_MESSAGE,
                        'errors' => null,
                    ], 409));
                }

                $existingNomination = Nomination::where('nominee_id', $nominee->id)
                    ->where('category_id', $category->id)
                    ->first();

                if ($existingNomination) {
                    abort(response()->json([
                        'success' => false,
                        'message' => 'This nominee has already been nominated in this category',
                        'errors' => null,
                    ], 409));
                }

                return Nomination::create([
                    'nominee_id' => $nominee->id,
                    'category_id' => $category->id,
                    'nominated_by' => $nominator->id,
                    'nominator_ip' => $nominatorIp,
                    'nominator_device_hash' => $deviceHash,
                    'nominator_user_agent' => blank($userAgent) ? null : $userAgent,
                    'nomination_reason' => $request->nomination_reason,
                    'achievement_documents' => $achievementDocuments,
                    'achievement_links' => $achievementLinks,
                    'evaluation_status' => 'pending',
                ]);
            });
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                return $this->errorResponse($this->duplicateMessageFromQueryException($exception), null, 409);
            }

            throw $exception;
        }

        $nomination->load('nominee', 'category', 'nominatedBy');

        return $this->successResponse(
            $nomination,
            'Nomination created successfully',
            201
        );
    }

    private function africanCountryNames(): array
    {
        return array_column(config('african_countries', []), 'name');
    }

    private function normalizedAchievementLinks(Request $request): array
    {
        $links = $request->input('achievement_links', []);
        $entries = is_array($links) ? $links : [$links];

        return collect($entries)
            ->flatMap(fn ($link) => preg_split('/\r\n|\r|\n/', (string) $link) ?: [])
            ->flatMap(fn ($link) => $this->extractSubmittedUrls((string) $link))
            ->filter()
            ->map(fn ($link) => $this->normalizeSubmittedUrl($link))
            ->filter(fn ($link) => filter_var($link, FILTER_VALIDATE_URL))
            ->unique()
            ->take(5)
            ->values()
            ->all();
    }

    private function extractSubmittedUrls(string $value): array
    {
        $value = trim($value);

        if ($value === '') {
            return [];
        }

        preg_match_all('/(?:https?:\/\/|www\.)[^\s,;<>]+|(?<!@)\b[a-z0-9][a-z0-9.-]+\.[a-z]{2,}(?:\/[^\s,;<>]*)?/i', $value, $matches);

        return collect($matches[0] ?? [])
            ->map(fn ($url) => rtrim($url, '.,;:!?)"]}'))
            ->filter()
            ->values()
            ->all();
    }

    private function normalizeSubmittedUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (preg_match('/^[a-z][a-z0-9+.-]*:\/\//i', $url)) {
            return $url;
        }

        if (preg_match('/^(www\.|[^\s@]+\.[^\s@]+)/i', $url)) {
            return 'https://'.$url;
        }

        return $url;
    }

    private function hasUploadedAchievementDocuments(Request $request): bool
    {
        return collect((array) $request->file('achievement_documents', []))
            ->filter()
            ->isNotEmpty();
    }

    private function storeProfileImage(Request $request): ?string
    {
        if (! $request->hasFile('profile_image_file')) {
            return null;
        }

        $path = $request->file('profile_image_file')->store('nominees/profile-images', 'public');

        return $this->publicStorageUrl($path);
    }

    private function storeAchievementDocuments(Request $request): array
    {
        return collect((array) $request->file('achievement_documents', []))
            ->filter()
            ->map(function ($file) {
                $path = $file->store('nominations/achievement-documents', 'public');

                return [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'url' => $this->publicStorageUrl($path),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            })
            ->values()
            ->all();
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }

    private function makeDeviceHash(Request $request): string
    {
        return hash('sha256', implode('|', [
            $request->ip() ?: 'unknown-ip',
            (string) $request->userAgent(),
            (string) $request->input('device_fingerprint', ''),
        ]));
    }

    private function deviceHasAlreadyNominated(?string $ipAddress, string $deviceHash): bool
    {
        return Nomination::where(function ($query) use ($ipAddress, $deviceHash) {
            $query->where('nominator_device_hash', $deviceHash);

            if (filled($ipAddress)) {
                $query->orWhere('nominator_ip', $ipAddress);
            }
        })->exists();
    }

    private function duplicateMessageFromQueryException(QueryException $exception): string
    {
        $databaseMessage = strtolower($exception->getMessage().' '.implode(' ', array_map('strval', $exception->errorInfo ?? [])));

        if (str_contains($databaseMessage, 'nominator_ip')
            || str_contains($databaseMessage, 'nominator_device_hash')
            || str_contains($databaseMessage, 'nominations_nominator_ip_unique')
            || str_contains($databaseMessage, 'nominations_nominator_device_hash_unique')) {
            return self::DEVICE_ALREADY_NOMINATED_MESSAGE;
        }

        return 'You have already submitted a nomination';
    }

    public function update(Request $request, Nomination $nomination)
    {
        $request->validate([
            'nomination_reason' => 'nullable|string',
        ]);

        if ($nomination->evaluation_status !== 'pending') {
            return $this->errorResponse('Cannot update evaluated nominations', null, 403);
        }

        $nomination->update($request->only(['nomination_reason']));

        return $this->successResponse($nomination->load('nominee', 'category'), 'Nomination updated successfully');
    }

    public function evaluate(Request $request, Nomination $nomination)
    {
        $request->validate([
            'evaluation_status' => 'required|in:approved,rejected',
            'evaluator_notes' => 'nullable|string',
        ]);

        $nomination->update([
            'evaluation_status' => $request->evaluation_status,
            'evaluator_notes' => $request->evaluator_notes,
            'evaluated_by' => auth()->id(),
            'evaluated_at' => now(),
        ]);

        if ($request->evaluation_status === 'approved') {
            $nomination->nominee->update(['status' => 'approved']);
        } else {
            $nomination->nominee->update([
                'status' => 'rejected',
                'rejection_reason' => $request->evaluator_notes,
            ]);
        }

        return $this->successResponse($nomination->load('nominee'), 'Nomination evaluated successfully');
    }

    public function approve(Nomination $nomination)
    {
        if ($nomination->evaluation_status === 'pending') {
            $nomination->update([
                'evaluation_status' => 'approved',
                'evaluated_by' => auth()->id(),
                'evaluated_at' => now(),
            ]);
            $nomination->nominee->update(['status' => 'approved']);
        }

        return $this->successResponse($nomination, 'Nomination approved successfully');
    }
}
