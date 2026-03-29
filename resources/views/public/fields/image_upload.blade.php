<div x-data="imageUploadField('{{ $field->id }}', @js($t))" x-init="init()">
    <label class="block text-sm font-medium text-white mb-1">
        {{ $field->label }}
        @if($field->required) <span class="text-red-400">*</span> @endif
    </label>
    @if($field->description)
        <p class="text-xs text-white/40 mb-2">{{ $field->description }}</p>
    @endif

    {{-- Dropzone --}}
    <div x-show="!imageSelected"
         @dragover.prevent="dragover = true" @dragleave="dragover = false"
         @drop.prevent="dragover = false; handleFile($event.dataTransfer.files[0])"
         @click="$refs.fileInput.click()"
         :class="dragover ? 'border-brand-500/50 bg-brand-500/5' : 'border-white/20'"
         class="border-2 border-dashed rounded-xl p-8 text-center cursor-pointer transition-colors hover:border-brand-500/40">
        <svg class="w-8 h-8 mx-auto text-white/20 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p class="text-white/40 text-sm">{{ $t['drop_image'] }}</p>
        <p class="text-white/25 text-xs mt-1">{{ $t['image_formats'] }}</p>
    </div>
    <input type="file" x-ref="fileInput" accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFile($event.target.files[0])">

    {{-- Crop area --}}
    <div x-show="imageSelected && !cropDone" x-transition class="space-y-3">
        <div class="rounded-xl overflow-hidden bg-black" style="max-height: 400px">
            <img x-ref="cropTarget" class="max-w-full">
        </div>
        <div class="flex gap-2">
            <button type="button" @click="confirmCrop()"
                    class="px-4 py-2 bg-brand-500 text-black rounded-lg text-sm font-semibold hover:bg-brand-400 transition-colors">
                {{ $t['use_this_crop'] }}
            </button>
            <button type="button" @click="reset()"
                    class="px-4 py-2 bg-white/10 text-white rounded-lg text-sm hover:bg-white/15 transition-colors">
                {{ $t['cancel'] }}
            </button>
        </div>
    </div>

    {{-- Preview --}}
    <div x-show="cropDone" class="flex items-center gap-4">
        <img :src="previewUrl" class="w-20 h-20 rounded-lg object-cover border border-white/10">
        <button type="button" @click="reset()" class="text-sm text-white/40 hover:text-white underline">
            {{ $t['change_image'] }}
        </button>
    </div>

    {{-- Existing image (edit mode) --}}
    @if($value && !str_starts_with((string)$value, 'data:'))
        <div x-show="!imageSelected && !cropDone && !changed" class="flex items-center gap-4 mt-2">
            <img src="{{ asset('storage/' . $value) }}" class="w-20 h-20 rounded-lg object-cover border border-white/10">
            <button type="button" @click="changed = true; $refs.fileInput.click()" class="text-sm text-white/40 hover:text-white underline">
                {{ $t['change_image'] }}
            </button>
        </div>
    @endif

    <input type="hidden" name="field_{{ $field->id }}" x-ref="hiddenInput" :value="imageData">
</div>

<script>
function imageUploadField(fieldId, t) {
    return {
        imageSelected: false,
        cropDone: false,
        cropper: null,
        previewUrl: '',
        imageData: '',
        dragover: false,
        changed: false,

        init() {},

        handleFile(file) {
            if (!file || !file.type.startsWith('image/')) {
                alert(t.invalid_image);
                return;
            }
            if (file.size > 10 * 1024 * 1024) {
                alert(t.image_too_large);
                return;
            }

            this.changed = true;
            const reader = new FileReader();
            reader.onload = (e) => {
                this.imageSelected = true;
                this.$nextTick(() => {
                    const img = this.$refs.cropTarget;
                    img.src = e.target.result;
                    if (this.cropper) this.cropper.destroy();
                    this.cropper = new Cropper(img, {
                        viewMode: 1,
                        background: false,
                        autoCropArea: 1,
                    });
                });
            };
            reader.readAsDataURL(file);
        },

        confirmCrop() {
            const canvas = this.cropper.getCroppedCanvas({
                maxWidth: 1920,
                maxHeight: 1920,
            });
            this.previewUrl = canvas.toDataURL('image/jpeg', 0.8);
            this.imageData = this.previewUrl;
            this.cropDone = true;
            this.imageSelected = false;
            this.cropper.destroy();
            this.cropper = null;
        },

        reset() {
            this.imageSelected = false;
            this.cropDone = false;
            this.previewUrl = '';
            this.imageData = '';
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            this.$refs.fileInput.value = '';
        }
    };
}
</script>
