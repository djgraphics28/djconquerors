<?php

use Livewire\Volt\Component;
use Livewire\WithFileUploads;
use App\Models\BinanceApkDownload;

new class extends Component {
    use WithFileUploads;

    public $apkId;
    public $app_type = 'binance';
    public $title = '';
    public $description = '';
    public $download_url = '';
    public $version = '';
    public $is_active = true;
    public $logo = null;               // new logo upload
    public $currentLogoUrl = null;     // existing logo preview
    public $apkFile = null;            // new APK file upload
    public $currentApkFileName = null; // existing APK filename
    public $editMode = false;
    public $showModal = false;
    public $showDeleteModal = false;
    public $apkToDelete = null;

    public function rules(): array
    {
        return [
            'app_type'     => 'required|in:binance,okx,bitget,other',
            'title'        => 'required|string|max:255',
            'description'  => 'nullable|string|max:2000',
            'download_url' => 'nullable|url|max:500',
            'version'      => 'nullable|string|max:50',
            'is_active'    => 'boolean',
            'logo'         => 'nullable|image|max:2048',
            'apkFile'      => 'nullable|file|max:512000',
        ];
    }

    public function getApksProperty()
    {
        return BinanceApkDownload::orderBy('created_at', 'desc')->get();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editMode  = false;
        $this->showModal = true;
    }

    public function edit($id): void
    {
        $apk = BinanceApkDownload::findOrFail($id);
        $this->apkId          = $id;
        $this->app_type       = $apk->app_type ?? 'binance';
        $this->title          = $apk->title;
        $this->description    = $apk->description;
        $this->download_url   = $apk->download_url;
        $this->version        = $apk->version;
        $this->is_active      = $apk->is_active;
        $this->currentLogoUrl      = $apk->getFirstMediaUrl('logo');
        $this->logo                = null;
        $this->currentApkFileName  = $apk->getFirstMedia('apk')?->file_name;
        $this->apkFile             = null;
        $this->editMode            = true;
        $this->showModal           = true;
    }

    public function save(): void
    {
        $this->validate();

        // At least one download source is required
        $hasExistingApk = $this->editMode
            && BinanceApkDownload::find($this->apkId)?->getFirstMedia('apk');
        if (!$this->apkFile && !$hasExistingApk && !$this->download_url) {
            $this->addError('download_url', 'Please provide a Google Drive link or upload an APK file.');
            return;
        }

        $data = [
            'app_type'     => $this->app_type,
            'title'        => $this->title,
            'description'  => $this->description,
            'download_url' => $this->download_url ?: null,
            'version'      => $this->version,
            'is_active'    => $this->is_active,
        ];

        if ($this->editMode) {
            $apk = BinanceApkDownload::findOrFail($this->apkId);
            $apk->update($data);
            if ($this->logo) {
                $apk->clearMediaCollection('logo');
                $apk->addMedia($this->logo->getRealPath())
                    ->usingFileName($this->logo->getClientOriginalName())
                    ->toMediaCollection('logo');
            }
            if ($this->apkFile) {
                $apk->clearMediaCollection('apk');
                $apk->addMedia($this->apkFile->getRealPath())
                    ->usingFileName($this->apkFile->getClientOriginalName())
                    ->toMediaCollection('apk');
            }
            session()->flash('message', 'Download link updated successfully.');
        } else {
            $apk = BinanceApkDownload::create($data);
            if ($this->logo) {
                $apk->addMedia($this->logo->getRealPath())
                    ->usingFileName($this->logo->getClientOriginalName())
                    ->toMediaCollection('logo');
            }
            if ($this->apkFile) {
                $apk->addMedia($this->apkFile->getRealPath())
                    ->usingFileName($this->apkFile->getClientOriginalName())
                    ->toMediaCollection('apk');
            }
            session()->flash('message', 'Download link created successfully.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete($id): void
    {
        $this->apkToDelete     = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        $apk = BinanceApkDownload::findOrFail($this->apkToDelete);
        $apk->clearMediaCollection('logo');
        $apk->clearMediaCollection('apk');
        $apk->delete();
        $this->showDeleteModal = false;
        $this->apkToDelete     = null;
        session()->flash('message', 'Download link deleted.');
    }

    public function toggleActive($id): void
    {
        $apk             = BinanceApkDownload::findOrFail($id);
        $apk->is_active  = !$apk->is_active;
        $apk->save();
    }

    private function resetForm(): void
    {
        $this->apkId          = null;
        $this->app_type       = 'binance';
        $this->title          = '';
        $this->description    = '';
        $this->download_url   = '';
        $this->version        = '';
        $this->is_active      = true;
        $this->logo               = null;
        $this->currentLogoUrl     = null;
        $this->apkFile            = null;
        $this->currentApkFileName = null;
        $this->resetErrorBag();
    }
}; ?>

<div>
    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Manage APK Downloads</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Manage Android APK download links for Binance, OKX, Bitget, and other apps.</p>
        </div>
        <button wire:click="openCreate"
            class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-500 text-black font-semibold px-4 py-2 rounded-xl transition-colors duration-150 text-sm">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add Link
        </button>
    </div>

    @if (session('message'))
        <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 rounded-xl px-4 py-3 text-sm">
            {{ session('message') }}
        </div>
    @endif

    <!-- Table -->
    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
        @if ($this->apks->isEmpty())
            <div class="p-14 text-center">
                <div class="w-14 h-14 bg-yellow-50 dark:bg-yellow-900/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300">No download links yet</h3>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Click "Add Link" to create your first entry.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs uppercase text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="px-6 py-3 text-left">Logo</th>
                        <th class="px-6 py-3 text-left">App</th>
                        <th class="px-6 py-3 text-left">Title / Version</th>
                        <th class="px-6 py-3 text-left">Download Source</th>
                        <th class="px-6 py-3 text-center">Status</th>
                        <th class="px-6 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($this->apks as $apk)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <td class="px-6 py-4">
                                @if ($apk->getFirstMediaUrl('logo'))
                                    <img src="{{ $apk->getFirstMediaUrl('logo') }}" alt="logo" class="w-10 h-10 rounded-xl object-contain bg-yellow-50 dark:bg-yellow-900/20 p-1">
                                @else
                                    <div class="w-10 h-10 rounded-xl bg-yellow-50 dark:bg-yellow-900/20 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-yellow-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ match($apk->app_type) {
                                        'binance' => 'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300',
                                        'okx'     => 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300',
                                        'bitget'  => 'bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300',
                                        default   => 'bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300',
                                    } }}">
                                    {{ $apk->app_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900 dark:text-white">{{ $apk->title }}</p>
                                @if ($apk->version)
                                    <p class="text-xs text-gray-400 dark:text-gray-500">v{{ $apk->version }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($apk->getFirstMediaUrl('apk'))
                                    <span class="inline-flex items-center gap-1.5 px-2 py-1 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300">
                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        APK Uploaded
                                    </span>
                                    @if ($apk->download_url)
                                        <a href="{{ $apk->download_url }}" target="_blank" rel="noopener noreferrer" class="text-blue-400 hover:underline block text-xs mt-1 truncate max-w-xs">Drive link (fallback)</a>
                                    @endif
                                @elseif ($apk->download_url)
                                    <a href="{{ $apk->download_url }}" target="_blank" rel="noopener noreferrer"
                                        class="text-blue-500 hover:underline truncate max-w-xs block text-xs">
                                        {{ $apk->download_url }}
                                    </a>
                                @else
                                    <span class="text-xs text-gray-400 dark:text-gray-500 italic">No source set</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <button wire:click="toggleActive({{ $apk->id }})"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium transition-colors
                                        {{ $apk->is_active
                                            ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 hover:bg-green-200 dark:hover:bg-green-900/50'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $apk->is_active ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                    {{ $apk->is_active ? 'Active' : 'Inactive' }}
                                </button>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <button wire:click="edit({{ $apk->id }})"
                                        class="p-1.5 rounded-lg text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $apk->id }})"
                                        class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <!-- Create / Edit Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 overflow-y-auto">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-lg my-auto flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex-shrink-0">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">
                        {{ $editMode ? 'Edit Download Link' : 'Add Download Link' }}
                    </h2>
                    <button wire:click="$set('showModal', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <form wire:submit.prevent="save" class="px-6 py-5 space-y-4 overflow-y-auto flex-1">
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">App Type</label>
                        <select wire:model="app_type" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            <option value="binance">Binance</option>
                            <option value="okx">OKX</option>
                            <option value="bitget">Bitget</option>
                            <option value="other">Other</option>
                        </select>
                        @error('app_type') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Title</label>
                        <input wire:model="title" type="text" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Binance for Android" />
                        @error('title') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <!-- Download Source -->
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400">Download Source <span class="font-normal text-red-400">*</span></label>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Upload an APK file directly, or provide a Google Drive link. If both are set, the uploaded file takes priority.</p>
                        </div>

                        <!-- Option 1: Upload APK -->
                        <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-4 space-y-2">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Option 1 — Upload APK File</p>

                            @if ($editMode && $currentApkFileName && !$apkFile)
                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
                                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span>Current: <span class="font-medium text-gray-700 dark:text-gray-200">{{ $currentApkFileName }}</span></span>
                                </div>
                            @endif

                            @if ($apkFile)
                                <div class="flex items-center gap-2 text-xs text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg px-3 py-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <span>Selected: <span class="font-medium">{{ $apkFile->getClientOriginalName() }}</span></span>
                                </div>
                            @endif

                            <label class="flex items-center gap-2 cursor-pointer w-fit">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                    </svg>
                                    {{ $apkFile ? 'Change APK file' : 'Choose APK file' }}
                                </span>
                                <input type="file" wire:model="apkFile" accept=".apk,application/vnd.android.package-archive" class="hidden">
                            </label>
                            @error('apkFile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Divider -->
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-px bg-gray-200 dark:bg-gray-600"></div>
                            <span class="text-xs text-gray-400 dark:text-gray-500 font-medium">OR</span>
                            <div class="flex-1 h-px bg-gray-200 dark:bg-gray-600"></div>
                        </div>

                        <!-- Option 2: Google Drive link -->
                        <div class="border border-gray-200 dark:border-gray-600 rounded-xl p-4 space-y-2">
                            <p class="text-xs font-semibold text-gray-600 dark:text-gray-400">Option 2 — Google Drive Link</p>
                            <input wire:model="download_url" type="url" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="https://drive.google.com/file/d/..." />
                            <p class="text-xs text-gray-400 dark:text-gray-500">Share link must be set to &quot;Anyone with the link&quot;.</p>
                            @error('download_url') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Version <span class="font-normal text-gray-400">(optional)</span></label>
                        <input wire:model="version" type="text" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="e.g. 2.85.0" />
                        @error('version') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-1">Description <span class="font-normal text-gray-400">(optional)</span></label>
                        <textarea wire:model="description" rows="3" class="w-full rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Brief description shown to members..."></textarea>
                        @error('description') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <!-- Logo Upload -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 dark:text-gray-400 mb-2">Logo <span class="font-normal text-gray-400">(optional)</span></label>

                        @if ($editMode && $currentLogoUrl && !$logo)
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $currentLogoUrl }}" alt="Current logo" class="w-14 h-14 rounded-xl object-contain bg-yellow-50 dark:bg-yellow-900/20 p-1 border border-gray-200 dark:border-gray-600">
                                <span class="text-xs text-gray-500 dark:text-gray-400">Current logo. Upload a new one to replace it.</span>
                            </div>
                        @endif

                        @if ($logo)
                            <div class="flex items-center gap-3 mb-2">
                                <img src="{{ $logo->temporaryUrl() }}" alt="Preview" class="w-14 h-14 rounded-xl object-contain bg-yellow-50 dark:bg-yellow-900/20 p-1 border border-yellow-300">
                                <span class="text-xs text-gray-500 dark:text-gray-400">New logo preview</span>
                            </div>
                        @endif

                        <label class="flex items-center gap-2 cursor-pointer w-fit">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700 text-xs text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                                </svg>
                                Choose image
                            </span>
                            <input type="file" wire:model="logo" accept="image/*" class="hidden">
                        </label>
                        @error('logo') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model="is_active" class="sr-only peer">
                            <div class="w-10 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-yellow-400"></div>
                        </label>
                        <span class="text-sm text-gray-700 dark:text-gray-300">Active (visible to members)</span>
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showModal', false)"
                            class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 rounded-xl bg-yellow-400 hover:bg-yellow-500 text-black font-semibold text-sm transition-colors">
                            {{ $editMode ? 'Update' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Delete Confirmation Modal -->
    @if ($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl w-full max-w-sm p-6 text-center">
                <div class="w-12 h-12 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1">Delete Download Link?</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">This action cannot be undone.</p>
                <div class="flex gap-3 justify-center">
                    <button wire:click="$set('showDeleteModal', false)"
                        class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 text-gray-600 dark:text-gray-300 text-sm hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button wire:click="delete"
                        class="px-4 py-2 rounded-xl bg-red-500 hover:bg-red-600 text-white font-semibold text-sm transition-colors">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
