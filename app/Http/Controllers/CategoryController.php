<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends BaseController
{
    public function publicIndex(Request $request)
    {
        $perPage = $this->perPage($request, 100);

        $categories = Category::where('is_active', true)
            ->orderBy('position')
            ->orderBy('name')
            ->paginate($perPage);

        return $this->paginatedResponse($categories, 'Categories retrieved successfully')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function index(Request $request)
    {
        $perPage = $this->perPage($request, 15);

        $categories = Category::with('createdBy')
            ->orderBy('position')
            ->orderBy('name')
            ->paginate($perPage);

        return $this->paginatedResponse($categories, 'Categories retrieved successfully')
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories|max:255',
            'description' => 'nullable|string|max:5000',
            'icon' => 'nullable|string|max:100',
            'max_nominees' => 'nullable|integer|min:1',
            'position' => 'nullable|integer',
        ]);

        $category = Category::create([
            'name' => $request->name,
            'description' => $request->description,
            'icon' => $request->icon,
            'max_nominees' => $request->max_nominees ?? 10,
            'position' => $request->position ?? 0,
            'created_by' => auth()->id(),
        ]);

        return $this->successResponse($category->load('createdBy'), 'Category created successfully', 201);
    }

    public function show(Category $category)
    {
        return $this->successResponse($category->load('createdBy'), 'Category retrieved successfully');
    }

    public function update(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'nullable|string|max:255|unique:categories,name,'.$category->id,
            'description' => 'nullable|string|max:5000',
            'icon' => 'nullable|string|max:100',
            'max_nominees' => 'nullable|integer|min:1',
            'position' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $category->update($request->only(['name', 'description', 'icon', 'max_nominees', 'position', 'is_active']));

        return $this->successResponse($category->load('createdBy'), 'Category updated successfully');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return $this->successResponse(null, 'Category deleted successfully');
    }

    private function perPage(Request $request, int $default): int
    {
        $perPage = (int) $request->input('per_page', $default);

        return max(1, min($perPage, 100));
    }
}
