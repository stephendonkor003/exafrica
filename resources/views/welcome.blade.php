@extends('layouts.app')

@php
    $publicCategories = collect($publicCategories ?? []);
    $categoryColorClasses = ['pink', 'teal', 'purple', 'orange', 'green', 'blue', 'maroon', 'gold', 'indigo'];
    $featuredCategory = $publicCategories->first();
@endphp

@section('content')

<!-- ACCOUNT SECTION -->
<div class="section-content" id="section-account" style="display:none;">
    <h1 class="section-title">Account</h1>
    <div class="app-panel">
        <div>
            <h2 class="section-subtitle">Create an account</h2>
            <p class="panel-copy">A voter account is required so nominations and votes can be saved securely in the database.</p>
            <div class="auth-status" id="authStatus">Checking account status...</div>
        </div>
        <div class="auth-actions">
            <button type="button" class="btn-submit" id="logoutBtn" style="display:none;">Sign Out</button>
        </div>
    </div>

    <div class="auth-grid">
        <form class="nomination-form app-form" id="loginForm">
            <h2 class="section-subtitle">Sign In</h2>
            <div class="form-group full-width">
                <label>Email *</label>
                <input name="email" type="email" required placeholder="you@example.com">
            </div>
            <div class="form-group full-width">
                <label>Password *</label>
                <input name="password" type="password" required minlength="6" placeholder="Your password">
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Sign In</button>
            </div>
            <div class="form-message" id="loginMessage"></div>
        </form>

        <form class="nomination-form app-form" id="registerForm">
            <h2 class="section-subtitle">Create Account</h2>
            <div class="form-group full-width">
                <label>Name *</label>
                <input name="name" type="text" required placeholder="Your full name">
            </div>
            <div class="form-group full-width">
                <label>Email *</label>
                <input name="email" type="email" required placeholder="you@example.com">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password *</label>
                    <input name="password" type="password" required minlength="8" placeholder="Minimum 8 characters">
                </div>
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input name="password_confirmation" type="password" required minlength="8" placeholder="Repeat password">
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Create Account</button>
            </div>
            <div class="form-message" id="registerMessage"></div>
        </form>
    </div>
</div>

<!-- ABOUT SECTION -->
<div class="section-content" id="section-about" style="display:none;">
    <h1 class="section-title">About</h1>
    <div class="about-body">
        <p><strong>Extraordinary Africans</strong> showcases and celebrates the continent's brightest stars.
        It is a global stage where the spotlight shines on exceptional individuals, highlighting
        their extraordinary contributions to their communities, the continent and the world
        through value-driven, innovative, scalable and impactful initiatives.</p>
        <p><strong>Extraordinary Africans</strong> is more than a competition; it's a movement igniting recognition,
        appreciation, and global acclaim for Africa's hidden gems.</p>
    </div>

    <div class="section-divider"></div>

    <h2 class="section-subtitle">Impact and Vision</h2>
    <div class="about-body">
        <p>Aligned with the African Union's Agenda 2063, this competition envisions a prosperous,
        united Africa driven by its own citizens. As inspiring stories of resilience, diversity, and
        innovation are shared with the world, more changemakers from all over Africa will find
        in them a spark of possibility, fueling their ambitions to <span class="highlight-text">create positive change.</span></p>

        <p>The Extraordinary Africans initiative aims to amplify Africa's voices and achievements
        on the global stage, fostering a culture of excellence and inspiring future generations to
        dream big and act boldly in service of the continent.</p>
    </div>

    <div class="section-divider"></div>

    <h2 class="section-subtitle">Our Mission</h2>
    <div class="about-grid">
        <div class="about-card"
             data-modal
             data-modal-icon="fa fa-star"
             data-modal-bg="#4A1628"
             data-modal-title="Recognize Excellence"
             data-modal-text="Celebrate extraordinary Africans who are making a difference in their communities and across the continent. We shine a spotlight on individuals whose contributions transform lives and build a better Africa.">
            <div class="about-card-icon"><i class="fa fa-star"></i></div>
            <h3>Recognize Excellence</h3>
            <p>Celebrate extraordinary Africans who are making a difference in their communities and across the continent.</p>
        </div>
        <div class="about-card"
             data-modal
             data-modal-icon="fa fa-globe-africa"
             data-modal-bg="#4A1628"
             data-modal-title="Global Stage"
             data-modal-text="Provide a global platform for African innovators and change-makers to share their stories and inspire others. We amplify Africa's voices and achievements on the world stage, fostering a culture of excellence.">
            <div class="about-card-icon"><i class="fa fa-globe-africa"></i></div>
            <h3>Global Stage</h3>
            <p>Provide a global platform for African innovators and change-makers to share their stories and inspire others.</p>
        </div>
        <div class="about-card"
             data-modal
             data-modal-icon="fa fa-handshake"
             data-modal-bg="#4A1628"
             data-modal-title="Drive Change"
             data-modal-text="Fuel Africa's transformation by recognising initiatives aligned with Agenda 2063's aspirations. We inspire future generations to dream big and act boldly in service of the continent.">
            <div class="about-card-icon"><i class="fa fa-handshake"></i></div>
            <h3>Drive Change</h3>
            <p>Fuel Africa's transformation by recognising initiatives aligned with Agenda 2063's aspirations.</p>
        </div>
    </div>
</div>

<!-- CATEGORIES SECTION -->
<div class="section-content" id="section-categories" style="display:none;">
    <h1 class="section-title">Categories and Descriptions</h1>
    <div class="about-body">
        <p>Welcome to the <strong>Extraordinary Africans</strong> Competition Categories page! Here, you will
        find the various categories under which projects and initiatives can be submitted. Each
        category aligns with the African Union's Agenda 2063, reflecting our commitment to
        recognising and celebrating initiatives that contribute to Africa's growth and
        transformation. Whether projects focus on gender and women's empowerment,
        advancing trade, investment, or digital innovation, you can discover where your project fits
        and join us in shaping Africa's future.</p>
    </div>

    <div class="carousel-wrapper">
        <button class="carousel-btn prev" id="catPrev"><i class="fa fa-chevron-left"></i></button>
        <div class="carousel-track-container">
            <div class="carousel-track" id="catTrack">
                @if ($publicCategories->isNotEmpty())
                    @foreach ($publicCategories as $category)
                        @php
                            $colorClass = $categoryColorClasses[$loop->index % count($categoryColorClasses)];
                            $description = $category->description ?: 'Category details coming soon.';
                        @endphp
                        <div class="carousel-slide{{ $loop->first ? ' active' : '' }}">
                            <div class="category-card {{ $colorClass }}"
                                 data-modal
                                 data-modal-color-class="{{ $colorClass }}"
                                 data-modal-tag="#ExtraordinaryAfricans"
                                 data-modal-title="{{ $category->name }}"
                                 data-modal-text="{{ $description }}">
                                <div class="cat-card-inner">
                                    <div class="cat-tag">#ExtraordinaryAfricans</div>
                                    <h3>{{ $category->name }}</h3>
                                    <p>{{ \Illuminate\Support\Str::limit($description, 110) }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="carousel-slide active">
                        <div class="category-card maroon category-card-empty">
                            <div class="cat-card-inner">
                                <div class="cat-tag">#ExtraordinaryAfricans</div>
                                <h3>No categories available</h3>
                                <p>Categories will appear here once they are active.</p>
                            </div>
                        </div>
                    </div>
                    @if (false)
                <div class="carousel-slide">
                    <div class="category-card pink"
                         data-modal
                         data-modal-color-class="pink"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Gender and Women Empowerment"
                         data-modal-text="Celebrating women who are breaking barriers and championing equality across Africa. This category honours initiatives that empower women economically, socially, and politically — driving lasting change in communities.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Gender and Women Empowerment</h3>
                            <p>Money is the family Banking Revolution</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide active">
                    <div class="category-card teal"
                         data-modal
                         data-modal-color-class="teal"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Advancing Trade, Investment, and Industrialization"
                         data-modal-text="Trade Titans: Made in Africa, Bought by Africans, Invested in Africa's future. This category recognises leaders driving intra-African trade, building industries, and creating economic ecosystems that benefit the continent.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Advancing Trade, Investment, and Industrialization</h3>
                            <p>Trade Titans: Made in Africa, Bought by Africans, Invested in Africa's future</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide">
                    <div class="category-card purple"
                         data-modal
                         data-modal-color-class="purple"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Agricultural Development"
                         data-modal-text="Agri-innovation for Africa's sustainability. Honouring pioneers who are modernising African agriculture through technology, sustainable farming practices, and food security solutions that nourish communities.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Agricultural Development</h3>
                            <p>Agri-innovation for Africa's sustainability</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide">
                    <div class="category-card orange"
                         data-modal
                         data-modal-color-class="orange"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Digital Innovation and Technology"
                         data-modal-text="Tech Pioneers: Building Africa's Digital Future. Recognising innovators who are harnessing technology to solve African challenges — from fintech and healthtech to edtech and infrastructure.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Digital Innovation and Technology</h3>
                            <p>Tech Pioneers: Building Africa's Digital Future</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide">
                    <div class="category-card green"
                         data-modal
                         data-modal-color-class="green"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Environmental Sustainability"
                         data-modal-text="Green Africa: Champions of Climate and Conservation. Celebrating individuals and organisations leading Africa's green revolution — from renewable energy and conservation to climate resilience and sustainable communities.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Environmental Sustainability</h3>
                            <p>Green Africa: Champions of Climate and Conservation</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide">
                    <div class="category-card blue"
                         data-modal
                         data-modal-color-class="blue"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Health and Well-being"
                         data-modal-text="Health Heroes: Transforming African Healthcare Systems. Honouring visionaries improving healthcare access and quality across Africa — through medical innovation, public health advocacy, and community wellness programmes.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Health and Well-being</h3>
                            <p>Health Heroes: Transforming African Healthcare Systems</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide">
                    <div class="category-card maroon"
                         data-modal
                         data-modal-color-class="maroon"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Youth Leadership"
                         data-modal-text="Young Leaders: Shaping Africa's Tomorrow. Celebrating Africa's next generation of change-makers who are leading movements, building enterprises, and inspiring their peers to take bold action for a better continent.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Youth Leadership</h3>
                            <p>Young Leaders: Shaping Africa's Tomorrow</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide">
                    <div class="category-card gold"
                         data-modal
                         data-modal-color-class="gold"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Arts, Culture &amp; Heritage"
                         data-modal-text="Cultural Custodians: Preserving and Celebrating Africa's Rich Heritage. Recognising artists, cultural ambassadors, and heritage champions who use creativity to tell Africa's story and preserve its identity for future generations.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Arts, Culture &amp; Heritage</h3>
                            <p>Cultural Custodians: Preserving and Celebrating Africa's Rich Heritage</p>
                        </div>
                    </div>
                </div>
                <div class="carousel-slide">
                    <div class="category-card indigo"
                         data-modal
                         data-modal-color-class="indigo"
                         data-modal-tag="#ExtraordinaryAfricans"
                         data-modal-title="Education and Skills Development"
                         data-modal-text="Knowledge Builders: Empowering Africa through Education. Honouring educators, institutions, and initiatives transforming access to quality education and skills training — unlocking potential across the continent.">
                        <div class="cat-card-inner">
                            <div class="cat-tag">#ExtraordinaryAfricans</div>
                            <h3>Education and Skills Development</h3>
                            <p>Knowledge Builders: Empowering Africa through Education</p>
                        </div>
                    </div>
                </div>
                    @endif
                @endif
            </div>
        </div>
        <button class="carousel-btn next" id="catNext"><i class="fa fa-chevron-right"></i></button>
    </div>
    <div class="carousel-dots" id="catDots"></div>
</div>

<!-- NOMINATIONS SECTION -->
<div class="section-content" id="section-nominations" style="display:none;">
    <h1 class="section-title">Nominations</h1>
    <div class="about-body">
        <p>Do you know an extraordinary African whose contributions are transforming communities,
        driving innovation, and shaping the continent's future? Nominate them today and help us
        celebrate Africa's brightest stars on a global stage.</p>
    </div>

    <div class="section-divider"></div>

    <div class="approved-nominations-showcase">
        <h2 class="section-subtitle">Approved Nominations</h2>
        <div class="nomination-marquee" id="approvedNominationStrip" aria-live="polite">
            <div class="empty-state">Loading approved nominations...</div>
        </div>
        <div class="form-message" id="nominationVoteMessage"></div>
    </div>

    <div class="section-divider"></div>

    <h2 class="section-subtitle">How to Nominate</h2>
    <div class="steps-grid">
        <div class="step-card"
             data-modal
             data-modal-number="01"
             data-modal-bg="#4A1628"
             data-modal-title="Choose a Category"
             data-modal-text="Select the active category that best aligns with the nominee's work and impact. Each category reflects a key pillar of Africa's Agenda 2063 development goals.">
            <div class="step-number">01</div>
            <h3>Choose a Category</h3>
            <p>Select the active category that best aligns with the nominee's work and impact.</p>
        </div>
        <div class="step-card"
             data-modal
             data-modal-number="02"
             data-modal-bg="#4A1628"
             data-modal-title="Complete the Form"
             data-modal-text="Fill in the nomination form with detailed information about the nominee's achievements and impact. Ensure you provide accurate details including full name, country, category, and a compelling description of their work.">
            <div class="step-number">02</div>
            <h3>Complete the Form</h3>
            <p>Fill in the nomination form with detailed information about the nominee's achievements and impact.</p>
        </div>
        <div class="step-card"
             data-modal
             data-modal-number="03"
             data-modal-bg="#4A1628"
             data-modal-title="Submit Evidence"
             data-modal-text="Provide supporting materials such as photos, videos, or documents that demonstrate the nominee's work. Strong evidence significantly improves the chances of a nomination being considered by the judging panel.">
            <div class="step-number">03</div>
            <h3>Submit Evidence</h3>
            <p>Provide supporting materials such as photos, videos, or documents that demonstrate the nominee's work.</p>
        </div>
        <div class="step-card"
             data-modal
             data-modal-number="04"
             data-modal-bg="#4A1628"
             data-modal-title="Await Review"
             data-modal-text="Our panel of distinguished judges will carefully review all nominations and select the most deserving candidates. You will be notified once the review process is complete and results are announced.">
            <div class="step-number">04</div>
            <h3>Await Review</h3>
            <p>Our panel of judges will carefully review all nominations and select the most deserving candidates.</p>
        </div>
    </div>

    <div class="section-divider"></div>

    <div class="nomination-form-wrapper">
        <h2 class="section-subtitle">Submit a Nomination</h2>
        <div class="auth-status compact" id="nominationAuthStatus"></div>
        <form class="nomination-form" id="nominationForm" enctype="multipart/form-data">
            <div class="form-row">
                <div class="form-group">
                    <label>Nominee's Full Name *</label>
                    <input name="full_name" type="text" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label>Nominee Email</label>
                    <input name="email" type="email" placeholder="nominee@example.com">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Category *</label>
                    <select name="category_id" id="nominationCategory" required>
                        <option value="">{{ $publicCategories->isNotEmpty() ? 'Select a category' : 'No categories available' }}</option>
                        @foreach ($publicCategories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Country *</label>
                    <select name="country" id="nomineeCountry" required>
                        <option value="">Select African country</option>
                        @foreach (config('african_countries') as $country)
                            <option value="{{ $country['name'] }}">{{ $country['flag'] }} {{ $country['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group full-width">
                <label>Phone</label>
                <input name="phone" type="text" placeholder="Phone or WhatsApp">
            </div>
            <div class="form-group full-width">
                <label>Short Bio</label>
                <textarea name="bio" rows="3" placeholder="Brief background on the nominee..."></textarea>
            </div>
            <div class="form-group full-width">
                <label>Description of Achievement *</label>
                <textarea name="nomination_reason" rows="5" required placeholder="Describe the nominee's extraordinary contributions..."></textarea>
            </div>
            <div class="form-group full-width">
                <label>Nominee Profile Picture *</label>
                <input name="profile_image_file" type="file" accept="image/*" required>
                <span class="form-help">Upload a clear JPG, PNG, or WEBP image. Large photos are resized before submission.</span>
            </div>
            <div class="evidence-box">
                <div class="form-group full-width">
                    <label>Achievement Documents</label>
                    <input name="achievement_documents[]" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp" multiple>
                    <span class="form-help">Upload up to 5 files under 2 MB each, or paste evidence links below.</span>
                </div>
                <div class="form-group full-width">
                    <label>Achievement Links</label>
                    <textarea name="achievement_links" rows="3" placeholder="Paste website, article, video, or social media links. Put each link on a new line."></textarea>
                    <span class="form-help">Provide documents, links, or both. Paste up to 5 links; non-link text is ignored.</span>
                </div>
            </div>
            <div class="form-submit">
                <button type="submit" class="btn-submit">Submit Nomination</button>
            </div>
            <div class="form-message" id="nominationMessage"></div>
        </form>
    </div>
</div>

<!-- VOTING SECTION -->
<div class="section-content" id="section-voting" style="display:none;">
    <h1 class="section-title">Voting</h1>
    <div class="about-body">
        <p>Approved nominees appear here when voting opens. Select a category and vote once per category.</p>
    </div>
    <div class="app-panel">
        <div class="form-group">
            <label>Category</label>
            <select id="votingCategory">
                <option value="">{{ $publicCategories->isNotEmpty() ? 'Select a category' : 'No categories available' }}</option>
                @foreach ($publicCategories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <button type="button" class="btn-submit" id="refreshNomineesBtn">Refresh Nominees</button>
    </div>
    <div class="auth-status compact" id="votingAuthStatus"></div>
    <div class="form-message" id="voteMessage"></div>
    <div class="nominee-grid" id="nomineeGrid"></div>
</div>

<!-- FLOW OF EVENTS SECTION -->
<div class="section-content" id="section-flow" style="display:none;">
    <h1 class="section-title">Flow of Events</h1>
    <div class="about-body">
        <p>The Extraordinary Africans competition follows a structured timeline designed to ensure
        fairness, transparency, and maximum participation from across the continent.</p>
    </div>

    <div class="section-divider"></div>

    <div class="timeline">
        <div class="timeline-item left">
            <div class="timeline-dot"></div>
            <div class="timeline-card"
                 data-modal
                 data-modal-tag="Phase 1"
                 data-modal-bg="#4A1628"
                 data-modal-number="1"
                 data-modal-title="Call for Nominations"
                 data-modal-text="The competition opens for nominations across all active categories. Nominators and self-nominees can submit entries through the official portal."
                 data-modal-date="January – March 2026">
                <span class="timeline-phase">Phase 1</span>
                <h3>Call for Nominations</h3>
                <p>The competition opens for nominations across all active categories. Nominators and self-nominees can submit entries through the official portal.</p>
                <span class="timeline-tag">January – March 2026</span>
            </div>
        </div>
        <div class="timeline-item right">
            <div class="timeline-dot"></div>
            <div class="timeline-card"
                 data-modal
                 data-modal-tag="Phase 2"
                 data-modal-bg="#4A1628"
                 data-modal-number="2"
                 data-modal-title="Screening and Verification"
                 data-modal-text="All nominations are screened for eligibility and verified against the competition criteria. Incomplete submissions are flagged for review."
                 data-modal-date="April 2026">
                <span class="timeline-phase">Phase 2</span>
                <h3>Screening and Verification</h3>
                <p>All nominations are screened for eligibility and verified against the competition criteria. Incomplete submissions are flagged for review.</p>
                <span class="timeline-tag">April 2026</span>
            </div>
        </div>
        <div class="timeline-item left">
            <div class="timeline-dot"></div>
            <div class="timeline-card"
                 data-modal
                 data-modal-tag="Phase 3"
                 data-modal-bg="#4A1628"
                 data-modal-number="3"
                 data-modal-title="Judging Panel Review"
                 data-modal-text="A distinguished panel of judges evaluate all verified nominations. Each nomination is assessed on impact, innovation, scalability, and alignment with Agenda 2063."
                 data-modal-date="May – June 2026">
                <span class="timeline-phase">Phase 3</span>
                <h3>Judging Panel Review</h3>
                <p>A distinguished panel of judges evaluate all verified nominations. Each nomination is assessed on impact, innovation, scalability, and alignment with Agenda 2063.</p>
                <span class="timeline-tag">May – June 2026</span>
            </div>
        </div>
        <div class="timeline-item right">
            <div class="timeline-dot"></div>
            <div class="timeline-card"
                 data-modal
                 data-modal-tag="Phase 4"
                 data-modal-bg="#4A1628"
                 data-modal-number="4"
                 data-modal-title="Shortlisting"
                 data-modal-text="Top nominees in each category are shortlisted and notified. Shortlisted nominees are featured on the competition website and social media channels."
                 data-modal-date="July 2026">
                <span class="timeline-phase">Phase 4</span>
                <h3>Shortlisting</h3>
                <p>Top nominees in each category are shortlisted and notified. Shortlisted nominees are featured on the competition website and social media channels.</p>
                <span class="timeline-tag">July 2026</span>
            </div>
        </div>
        <div class="timeline-item left">
            <div class="timeline-dot"></div>
            <div class="timeline-card"
                 data-modal
                 data-modal-tag="Phase 5"
                 data-modal-bg="#4A1628"
                 data-modal-number="5"
                 data-modal-title="Public Voting"
                 data-modal-text="The public is invited to vote for their favourite nominees. Public voting forms part of the overall scoring mechanism and gives the community a voice in celebrating Africa's brightest."
                 data-modal-date="August 2026">
                <span class="timeline-phase">Phase 5</span>
                <h3>Public Voting</h3>
                <p>The public is invited to vote for their favourite nominees. Public voting forms part of the overall scoring mechanism.</p>
                <span class="timeline-tag">August 2026</span>
            </div>
        </div>
        <div class="timeline-item right">
            <div class="timeline-dot active-dot"></div>
            <div class="timeline-card highlight-card"
                 data-modal
                 data-modal-tag="Phase 6"
                 data-modal-bg="#E8A020"
                 data-modal-number="6"
                 data-modal-title="Awards Ceremony"
                 data-modal-text="Winners are announced and celebrated at a prestigious awards ceremony attended by African leaders, innovators, and changemakers. A night of recognition, inspiration, and continental pride."
                 data-modal-date="September 2026">
                <span class="timeline-phase">Phase 6</span>
                <h3>Awards Ceremony</h3>
                <p>Winners are announced and celebrated at a prestigious awards ceremony attended by African leaders, innovators, and changemakers.</p>
                <span class="timeline-tag">September 2026</span>
            </div>
        </div>
    </div>
</div>

<!-- JUDGES SECTION -->
<div class="section-content" id="section-judges" style="display:none;">
    <h1 class="section-title">JUDGES</h1>

    @if ($publicCategories->isNotEmpty())
        @foreach ($publicCategories as $category)
            @php
                $judgeDescription = $category->description
                    ? 'Judges for this category will review nominees connected to '.$category->description
                    : 'Judges for this category will be announced soon.';
            @endphp
            <div class="judges-category">
                <h2 class="judges-category-title">{{ $category->name }}</h2>
                <div class="judges-grid">
                    @for ($judgeSlot = 1; $judgeSlot <= 3; $judgeSlot++)
                        <div class="judge-card"
                             data-modal
                             data-modal-image="https://placehold.co/200x220/d4a574/4A1628?text=Judge"
                             data-modal-title="Judge to be announced"
                             data-modal-subtitle="{{ $category->name }}"
                             data-modal-tag="{{ $category->name }}"
                             data-modal-text="{{ $judgeDescription }}">
                            <div class="judge-img-wrap">
                                <img src="https://placehold.co/200x220/d4a574/4A1628?text=Judge" alt="Judge">
                            </div>
                            <p class="judge-name">Judge to be announced</p>
                            <p class="judge-title">{{ $category->name }}</p>
                        </div>
                    @endfor
                </div>
            </div>
        @endforeach
    @else
        <div class="empty-state">Judge categories will appear once categories are active.</div>
    @endif

    @if (false)
    <div class="judges-category">
        <h2 class="judges-category-title">Gender and Women Empowerment</h2>
        <div class="judges-grid">
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/d4a574/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Gender and Women Empowerment"
                 data-modal-text="A distinguished leader and advocate for gender equality across Africa, bringing decades of experience in policy, development, and women's empowerment to the judging panel.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/d4a574/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/c4956a/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Gender and Women Empowerment"
                 data-modal-text="A distinguished leader and advocate for gender equality across Africa, bringing decades of experience in policy, development, and women's empowerment to the judging panel.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/c4956a/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/b8824e/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Gender and Women Empowerment"
                 data-modal-text="A distinguished leader and advocate for gender equality across Africa, bringing decades of experience in policy, development, and women's empowerment to the judging panel.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/b8824e/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
        </div>
    </div>

    <div class="judges-category">
        <h2 class="judges-category-title">Advancing Trade, Investment, and Industrialisation</h2>
        <div class="judges-grid">
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/d4a574/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Trade, Investment and Industrialisation"
                 data-modal-text="A seasoned expert in African trade and investment, with a track record of building economic partnerships and championing industrialisation strategies across the continent.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/d4a574/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/c4956a/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Trade, Investment and Industrialisation"
                 data-modal-text="A seasoned expert in African trade and investment, with a track record of building economic partnerships and championing industrialisation strategies across the continent.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/c4956a/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/b8824e/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Trade, Investment and Industrialisation"
                 data-modal-text="A seasoned expert in African trade and investment, with a track record of building economic partnerships and championing industrialisation strategies across the continent.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/b8824e/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
        </div>
    </div>

    <div class="judges-category">
        <h2 class="judges-category-title">Agricultural Development</h2>
        <div class="judges-grid">
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/d4a574/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Agricultural Development"
                 data-modal-text="A pioneer in African agricultural innovation, dedicated to sustainable food systems and modernising farming practices to drive food security across the continent.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/d4a574/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/c4956a/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Agricultural Development"
                 data-modal-text="A pioneer in African agricultural innovation, dedicated to sustainable food systems and modernising farming practices to drive food security across the continent.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/c4956a/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
            <div class="judge-card"
                 data-modal
                 data-modal-image="https://placehold.co/200x220/b8824e/4A1628?text=Judge"
                 data-modal-title="Name of Judge"
                 data-modal-subtitle="and Title"
                 data-modal-tag="Agricultural Development"
                 data-modal-text="A pioneer in African agricultural innovation, dedicated to sustainable food systems and modernising farming practices to drive food security across the continent.">
                <div class="judge-img-wrap">
                    <img src="https://placehold.co/200x220/b8824e/4A1628?text=Judge" alt="Judge">
                </div>
                <p class="judge-name">Name of Judge</p>
                <p class="judge-title">and Title</p>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- WINNERS SECTION -->
<div class="section-content" id="section-winners" style="display:none;">
    <h1 class="section-title winners-title">WINNERS</h1>
    <div class="winners-grid">
        @if ($publicCategories->isNotEmpty())
            @foreach ($publicCategories as $category)
                @php
                    $winnerImageTone = $loop->first ? 'E8A020/fff?text=WINNER' : '4A1628/F5A623?text=Winner';
                    $winnerDescription = $category->description ?: 'Winner details for this category will be announced soon.';
                @endphp
                <div class="winner-card{{ $loop->first ? ' featured' : '' }}"
                     data-modal
                     data-modal-image="https://placehold.co/280x300/{{ $winnerImageTone }}"
                     data-modal-tag="{{ $loop->first ? 'Featured Category' : 'Category Winner' }}"
                     data-modal-title="{{ $category->name }}"
                     data-modal-text="{{ $winnerDescription }}">
                    <div class="winner-img-wrap">
                        <img src="https://placehold.co/280x300/{{ $winnerImageTone }}" alt="{{ $category->name }}">
                        <div class="winner-corner-tag{{ $loop->first ? ' gold-tag' : '' }}"><i class="fa {{ $loop->first ? 'fa-crown' : 'fa-trophy' }}"></i></div>
                        @if ($loop->first)
                            <div class="winner-featured-label">
                                <span class="winner-label-text">WINNER</span>
                                <p>{{ $category->name }}</p>
                            </div>
                        @endif
                        <div class="winner-overlay">
                            <p class="winner-name">{{ $category->name }}</p>
                            <p class="winner-desc">{{ \Illuminate\Support\Str::limit($winnerDescription, 120) }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="empty-state">Winners will appear once categories are active.</div>
        @endif

        @if (false)
        <div class="winner-card"
             data-modal
             data-modal-image="https://placehold.co/280x300/4A1628/F5A623?text=Winner"
             data-modal-tag="Category Winner"
             data-modal-title="Amira Sayed"
             data-modal-text="Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.">
            <div class="winner-img-wrap">
                <img src="https://placehold.co/280x300/4A1628/F5A623?text=Winner" alt="Winner">
                <div class="winner-corner-tag"><i class="fa fa-trophy"></i></div>
                <div class="winner-overlay">
                    <p class="winner-name">Amira Sayed</p>
                    <p class="winner-desc">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                </div>
            </div>
        </div>
        <div class="winner-card"
             data-modal
             data-modal-image="https://placehold.co/280x300/4A1628/F5A623?text=Winner"
             data-modal-tag="Category Winner"
             data-modal-title="Amira Sayed"
             data-modal-text="Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.">
            <div class="winner-img-wrap">
                <img src="https://placehold.co/280x300/4A1628/F5A623?text=Winner" alt="Winner">
                <div class="winner-corner-tag"><i class="fa fa-trophy"></i></div>
                <div class="winner-overlay">
                    <p class="winner-name">Amira Sayed</p>
                    <p class="winner-desc">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                </div>
            </div>
        </div>
        <div class="winner-card featured"
             data-modal
             data-modal-image="https://placehold.co/280x300/E8A020/fff?text=WINNER"
             data-modal-tag="Grand Winner"
             data-modal-title="Name of the Winner and Category"
             data-modal-text="Trade Titans: Made in Africa, Bought by Africans, Invested in Africa's future. An outstanding champion of continental trade and industrialisation whose work is reshaping economic landscapes across Africa.">
            <div class="winner-img-wrap">
                <img src="https://placehold.co/280x300/E8A020/fff?text=WINNER" alt="Winner">
                <div class="winner-corner-tag gold-tag"><i class="fa fa-crown"></i></div>
                <div class="winner-featured-label">
                    <span class="winner-label-text">WINNER</span>
                    <p>Name of the Winner and Category</p>
                </div>
                <div class="winner-overlay">
                    <p class="winner-name">Advancing Trade, Investment, and Industrialization</p>
                    <p class="winner-desc">Trade Titans: Made in Africa, Bought by Africans, Invested in Africa's future</p>
                </div>
            </div>
        </div>
        <div class="winner-card"
             data-modal
             data-modal-image="https://placehold.co/280x300/4A1628/F5A623?text=Winner"
             data-modal-tag="Category Winner"
             data-modal-title="Amira Sayed"
             data-modal-text="Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.">
            <div class="winner-img-wrap">
                <img src="https://placehold.co/280x300/4A1628/F5A623?text=Winner" alt="Winner">
                <div class="winner-corner-tag"><i class="fa fa-trophy"></i></div>
                <div class="winner-overlay">
                    <p class="winner-name">Amira Sayed</p>
                    <p class="winner-desc">Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@endsection
