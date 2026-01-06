<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $bonchat_id = '';
    public $riscoin_id = '';
    public $inviters_code = '';
    public $invested_amount = '';
    public $date_joined = '';
    public $birth_date = '';
    public $phone_number = '';
    public $avatar = null;
    public $uploadProgress = 0;
    public $isUploading = false;
    public $isBirthdayMention = true;
    public $isMonthlyMilestoneMention = true;
    public $gender = '';
    public $occupation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();

        $this->name = $user->name;
        $this->email = $user->email;
        $this->bonchat_id = $user->bonchat_id ?? '';
        $this->riscoin_id = $user->riscoin_id;
        $this->inviters_code = $user->inviters_code;
        $this->invested_amount = $user->invested_amount;
        $this->date_joined = date('Y-m-d', strtotime($user->date_joined));
        $this->birth_date = date('Y-m-d', strtotime($user->birth_date));        $this->phone_number = $user->phone_number;
        $this->isBirthdayMention = $user->is_birthday_mention == 1 ? true : false;
        $this->isMonthlyMilestoneMention = $user->is_monthly_milestone_mention == 1 ? true : false;
        $this->gender = $user->gender;
        $this->occupation = $user->occupation;

        // dd($user->getFirstMediaUrl('avatar'));
    }

    /**
     * Get the current avatar URL.
     */
    public function getAvatarUrlProperty()
    {
        if ($this->avatar) {
            return $this->avatar->temporaryUrl();
        }

        return Auth::user()->getFirstMEdiaUrl('avatar');
    }

    /**
     * Updated avatar property when file is selected.
     */
    public function updatedAvatar(): void
    {
        $this->validate([
            'avatar' => ['nullable', 'image', 'max:10240'], // 10MB max
        ]);

        // Reset upload state
        $this->uploadProgress = 0;
        $this->isUploading = false;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'bonchat_id' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'birth_date' => ['required', 'date'],
            'date_joined' => ['required', 'date'],
            'invested_amount' => ['required', 'numeric'],
            'inviters_code' => ['required', 'string', 'max:255'],
            'riscoin_id' => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:10240'],
            'gender' => ['required', 'string', 'max:50'],
            'occupation' => ['required', 'string', 'max:100'],
        ]);

        $validated['riscoin_id'] = strtoupper($validated['riscoin_id']);
        $validated['inviters_code'] = strtoupper($validated['inviters_code']);
        $validated['is_birthday_mention'] = $this->isBirthdayMention;
        $validated['is_monthly_milestone_mention'] = $this->isMonthlyMilestoneMention;

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        // Handle avatar upload
        if ($this->avatar) {
            $this->isUploading = true;
            $this->uploadProgress = 50;

            try {
                // Clear existing avatar first
                $user->clearMediaCollection('avatar');

                // Add new avatar
                $user
                    ->addMedia($this->avatar->getRealPath())
                    ->usingFileName('avatar_' . time() . '.' . $this->avatar->getClientOriginalExtension())
                    ->toMediaCollection('avatar');

                $this->uploadProgress = 100;
            } catch (\Exception $e) {
                // Handle upload error
                session()->flash('error', 'Failed to upload avatar: ' . $e->getMessage());
            }

            // Clear the temporary avatar
            $this->avatar = null;
            $this->isUploading = false;
            $this->uploadProgress = 0;
        }

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Remove the current avatar.
     */
    public function removeAvatar(): void
    {
        $user = Auth::user();
        $user->clearMediaCollection('avatar');

        $this->avatar = null;
        $this->dispatch('avatar-removed');
    }

    /**
     * Cancel avatar upload.
     */
    public function cancelAvatarUpload(): void
    {
        $this->avatar = null;
        $this->uploadProgress = 0;
        $this->isUploading = false;
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="__('Update your profile information and avatar')">
        <!-- Avatar Upload Section -->
        <div class="mb-6">
            <flux:text class="text-sm font-medium dark:text-white text-gray-900 mb-4">
                {{ __('Profile Picture') }}
            </flux:text>

            <div class="flex items-center gap-6">
                <!-- Current Avatar -->
                <div class="relative">
                    <img src="{{ $this->avatarUrl }}" alt="{{ Auth::user()->name }}"
                        class="w-20 h-20 rounded-full object-cover border-2 border-gray-200 dark:border-gray-700"
                        onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF'">

                    <!-- Remove button only shows when user has an avatar and no new upload -->
                    @if (Auth::user()->getFirstMediaUrl('avatar') && !$avatar)
                        <button type="button" wire:click="removeAvatar"
                            wire:confirm="Are you sure you want to remove your profile picture?"
                            class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition-colors">
                            ×
                        </button>
                    @endif
                </div>

                <!-- Upload Controls -->
                <div class="flex-1">
                    <div class="space-y-2">
                        @if (!$avatar)
                            <flux:button variant="outline" type="button"
                                onclick="document.getElementById('avatar-upload').click()" class="cursor-pointer">
                                {{ Auth::user()->getFirstMediaUrl('avatar') ? __('Change Avatar') : __('Upload Avatar') }}
                            </flux:button>
                        @endif

                        <input type="file" id="avatar-upload" wire:model="avatar"
                            accept="image/jpeg,image/png,image/jpg,image/gif" class="hidden">

                        @if ($avatar)
                            <div class="space-y-3">
                                <flux:text class="text-sm !dark:text-gray-400 !text-gray-600">
                                    {{ $avatar->getClientOriginalName() }}
                                    ({{ number_format($avatar->getSize() / 1024, 1) }} KB)
                                </flux:text>

                                <div class="flex gap-2">
                                    <flux:button variant="primary" type="button" wire:click="updateProfileInformation"
                                        class="cursor-pointer" :disabled="$isUploading">
                                        @if ($isUploading)
                                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10"
                                                    stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor"
                                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                </path>
                                            </svg>
                                        @endif
                                        {{ $isUploading ? __('Uploading...') : __('Save Avatar') }}
                                    </flux:button>

                                    <flux:button variant="outline" type="button" wire:click="cancelAvatarUpload"
                                        class="cursor-pointer" :disabled="$isUploading">
                                        {{ __('Cancel') }}
                                    </flux:button>
                                </div>
                            </div>
                        @else
                            <flux:text class="text-sm !dark:text-gray-400 !text-gray-600">
                                {{ __('JPG, PNG or GIF. Max 10MB.') }}
                            </flux:text>
                        @endif
                    </div>

                    <!-- Upload Progress -->
                    @if ($isUploading && $uploadProgress > 0)
                        <div class="mt-4">
                            <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700">
                                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                                    style="width: {{ $uploadProgress }}%"></div>
                            </div>
                            <flux:text class="text-xs mt-1 !dark:text-gray-400 !text-gray-600">
                                {{ $uploadProgress }}% {{ __('complete') }}
                            </flux:text>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Avatar Upload Error -->
            @error('avatar')
                <flux:text class="text-sm !dark:text-red-400 !text-red-600 mt-2">
                    {{ $message }}
                </flux:text>
            @enderror

            <!-- Flash Messages -->
            @if (session('error'))
                <flux:text class="text-sm !dark:text-red-400 !text-red-600 mt-2">
                    {{ session('error') }}
                </flux:text>
            @endif
        </div>

        <!-- Profile Form -->
        <form wire:submit="updateProfileInformation" class="w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus
                autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !auth()->user()->hasVerifiedEmail())
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer"
                                wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>
            <flux:input wire:model="bonchat_id" :label="__('Bonchat ID')" type="text" :placeholder="__('Bonchat ID')" />

            <flux:input wire:model="riscoin_id" :label="__('Riscoin ID')" type="text" disabled />
            <flux:input wire:model="inviters_code" :label="__('Inviters Code')" type="text" disabled />
            <flux:input wire:model="invested_amount" :label="__('Invested Amount (USD)')" type="text" disabled />
            <flux:input wire:model="date_joined" :label="__('Date Joined')" type="date" disabled />
            <flux:input wire:model="birth_date" :label="__('Birth Date')" type="date" />
            <flux:input wire:model="phone_number" :label="__('Phone Number')" type="tel" />
            <!-- Gender -->
            <flux:select wire:model="gender" :label="__('Gender')" required data-test="gender-select">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </flux:select>
            <!-- Occupation -->
            <flux:input wire:model="occupation" :label="__('Occupation')" type="text" required autocomplete="occupation"
                :placeholder="__('Occupation')" />


            {{-- Mentions --}}
            <div class="flex flex-col gap-4">
                <label class="flex items-center">
                    <input type="checkbox" wire:model="isBirthdayMention" class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="ml-2">{{ __('Do you want to be greeted on your Birthday?') }}</span> </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model="isMonthlyMilestoneMention"
                        class="form-checkbox h-5 w-5 text-blue-600">
                    <span class="ml-2">{{ __('Do you want to be mentioned on Monthly Milestones?') }}</span>
                </label>
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button"
                        :disabled="$isUploading">
                        @if ($isUploading)
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                        @endif
                        {{ $isUploading ? __('Saving...') : __('Save Changes') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        {{-- <livewire:settings.delete-user-form /> --}}
    </x-settings.layout>
</section>
