<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertCircle,
    CheckCircle2,
    FileSearch,
    FileUp,
    Loader2,
    ScanSearch,
    Sparkles,
    UploadCloud,
    X,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import UploadController from '@/actions/App/Http/Controllers/Upload/UploadController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

/**
 * The backend ingest endpoint (ISSUE 2.2) is not yet implemented.
 * Set this flag to `true` once the endpoint exists, then wire
 * `submitUpload()` to the Wayfinder action (see the TODO below).
 */
const UPLOAD_BACKEND_READY = false as boolean;

type Props = {
    maxFileSizeMb: number;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Upload', href: UploadController.url() },
];

// "What happens next" steps
type Step = { icon: Component; label: string; description: string };

const steps: Step[] = [
    {
        icon: UploadCloud,
        label: 'Parse',
        description: 'Your DST file is decoded and stitch data is extracted.',
    },
    {
        icon: ScanSearch,
        label: 'Render',
        description: 'A visual preview is generated showing stitch paths and density.',
    },
    {
        icon: FileSearch,
        label: 'QA Check',
        description: 'Automated rules check for jumps, density, color changes, and more.',
    },
    {
        icon: CheckCircle2,
        label: 'Report',
        description: 'A scored report is created with a risk rating and actionable notes.',
    },
];

// State
const isDraggingOver = ref(false);
const selectedFile = ref<File | null>(null);
const uploadProgress = ref(0);
const uploadState = ref<'idle' | 'uploading' | 'success' | 'error'>('idle');
const errorMessage = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

// Computed
const maxFileSizeBytes = computed(() => props.maxFileSizeMb * 1024 * 1024);
const isUploading = computed(() => uploadState.value === 'uploading');

const fileSizeDisplay = computed<string | null>(() => {
    if (!selectedFile.value) return null;
    const bytes = selectedFile.value.size;
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
});

// Validation
function validateFile(file: File): string | null {
    const ext = file.name.split('.').pop()?.toLowerCase();
    if (ext !== 'dst') {
        return `Only .dst files are accepted. You selected a .${ext ?? 'unknown'} file.`;
    }
    if (file.size > maxFileSizeBytes.value) {
        return `File exceeds the ${props.maxFileSizeMb} MB limit for your current plan.`;
    }
    return null;
}

// File selection
function selectFile(file: File): void {
    const validationError = validateFile(file);
    if (validationError) {
        errorMessage.value = validationError;
        uploadState.value = 'error';
        selectedFile.value = null;
        return;
    }
    selectedFile.value = file;
    errorMessage.value = null;
    uploadState.value = 'idle';
}

function onFileInputChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (file) selectFile(file);
}

function openFilePicker(): void {
    fileInputRef.value?.click();
}

function clearFile(): void {
    selectedFile.value = null;
    uploadState.value = 'idle';
    errorMessage.value = null;
    uploadProgress.value = 0;
    if (fileInputRef.value) fileInputRef.value.value = '';
}

// Drag & Drop
function onDragOver(event: DragEvent): void {
    event.preventDefault();
    isDraggingOver.value = true;
}

function onDragLeave(): void {
    isDraggingOver.value = false;
}

function onDrop(event: DragEvent): void {
    event.preventDefault();
    isDraggingOver.value = false;
    const file = event.dataTransfer?.files[0];
    if (file) selectFile(file);
}

// Upload
function submitUpload(): void {
    if (!selectedFile.value) return;

    if (!UPLOAD_BACKEND_READY) {
        errorMessage.value =
            'Upload backend not implemented yet (ISSUE 2.2). ' +
            'Once the ingest endpoint is ready, set UPLOAD_BACKEND_READY = true ' +
            'and wire the Wayfinder action inside submitUpload().';
        uploadState.value = 'error';
        return;
    }

    /**
     * TODO (ISSUE 2.2): Implement real upload here.
     *
     * import IngestController from '@/actions/App/Http/Controllers/Upload/IngestController';
     * import { router } from '@inertiajs/vue3';
     *
     * const formData = new FormData();
     * formData.append('file', selectedFile.value);
     * uploadState.value = 'uploading';
     * uploadProgress.value = 0;
     * errorMessage.value = null;
     *
     * router.post(IngestController.url(), formData, {
     *     forceFormData: true,
     *     onProgress: (p) => { uploadProgress.value = p.percentage ?? 0; },
     *     onSuccess: () => { uploadState.value = 'success'; },
     *     onError: (errors) => {
     *         uploadState.value = 'error';
     *         errorMessage.value = Object.values(errors).flat().join(' ');
     *     },
     * });
     */
}
</script>

<template>

    <Head title="Upload" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-8 p-6">
            <!-- Page header -->
            <div class="space-y-1">
                <h1 class="text-2xl font-bold tracking-tight">Upload Design File</h1>
                <p class="text-muted-foreground">
                    Upload a DST file to generate a QA report for your embroidery design.
                </p>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Upload area -->
                <div class="flex flex-col gap-4 lg:col-span-2">
                    <!-- Hidden file input -->
                    <input ref="fileInputRef" type="file" accept=".dst" class="sr-only" aria-label="Select DST file"
                        @change="onFileInputChange" />

                    <!-- Drag & drop zone (visible when no file is selected) -->
                    <button v-if="!selectedFile" type="button" :class="[
                        'group relative flex w-full cursor-pointer flex-col items-center justify-center gap-4 rounded-xl border-2 border-dashed px-8 py-16 text-center transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring',
                        isDraggingOver
                            ? 'border-primary bg-primary/5'
                            : 'border-border hover:border-primary/50 hover:bg-muted/40',
                    ]" aria-label="Drop DST file here, or click to browse" @click="openFilePicker"
                        @dragover="onDragOver" @dragleave="onDragLeave" @drop="onDrop">
                        <div :class="[
                            'flex size-16 items-center justify-center rounded-full transition-colors',
                            isDraggingOver ? 'bg-primary/10' : 'bg-muted group-hover:bg-primary/10',
                        ]">
                            <UploadCloud :class="[
                                'size-8 transition-colors',
                                isDraggingOver
                                    ? 'text-primary'
                                    : 'text-muted-foreground group-hover:text-primary',
                            ]" />
                        </div>

                        <div class="space-y-1">
                            <p class="text-base font-semibold">
                                <span v-if="isDraggingOver">Drop it here</span>
                                <span v-else>Drag &amp; drop your DST file here</span>
                            </p>
                            <p class="text-muted-foreground text-sm">
                                or
                                <span class="text-primary font-medium underline-offset-2 hover:underline">
                                    click to browse
                                </span>
                            </p>
                        </div>

                        <div class="flex flex-wrap justify-center gap-2">
                            <Badge variant="secondary">.dst files only</Badge>
                            <Badge variant="secondary">Max {{ props.maxFileSizeMb }} MB</Badge>
                        </div>
                    </button>

                    <!-- Selected file card -->
                    <Card v-if="selectedFile" class="overflow-hidden">
                        <CardContent class="flex items-start gap-4 p-4">
                            <div class="bg-primary/10 flex size-12 shrink-0 items-center justify-center rounded-lg">
                                <FileUp class="text-primary size-6" />
                            </div>

                            <div class="min-w-0 flex-1 space-y-1">
                                <p class="truncate text-sm font-medium">{{ selectedFile.name }}</p>
                                <p class="text-muted-foreground text-xs">{{ fileSizeDisplay }}</p>

                                <!-- Progress bar -->
                                <div v-if="isUploading" class="space-y-1 pt-1">
                                    <div class="bg-muted h-2 w-full overflow-hidden rounded-full">
                                        <div class="bg-primary h-full rounded-full transition-all duration-300 ease-out"
                                            :style="{ width: `${uploadProgress}%` }" />
                                    </div>
                                    <p class="text-muted-foreground text-xs tabular-nums">
                                        {{ uploadProgress }}% — uploading…
                                    </p>
                                </div>
                            </div>

                            <button v-if="!isUploading" type="button"
                                class="text-muted-foreground hover:text-foreground shrink-0 transition-colors"
                                aria-label="Remove selected file" @click="clearFile">
                                <X class="size-4" />
                            </button>
                        </CardContent>
                    </Card>

                    <!-- Error alert -->
                    <Alert v-if="uploadState === 'error' && errorMessage" variant="destructive">
                        <AlertCircle class="size-4" />
                        <AlertTitle>Upload failed</AlertTitle>
                        <AlertDescription>{{ errorMessage }}</AlertDescription>
                    </Alert>

                    <!-- Success alert -->
                    <Alert v-if="uploadState === 'success'"
                        class="border-green-200 bg-green-50 text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        <CheckCircle2 class="size-4 text-green-600 dark:text-green-400" />
                        <AlertTitle>Upload complete</AlertTitle>
                        <AlertDescription>
                            Your file has been received. Redirecting to your QA report…
                        </AlertDescription>
                    </Alert>

                    <!-- Action buttons -->
                    <div class="flex items-center gap-3">
                        <Button :disabled="!selectedFile || isUploading || uploadState === 'success'" class="gap-2"
                            @click="submitUpload">
                            <Loader2 v-if="isUploading" class="size-4 animate-spin" />
                            <UploadCloud v-else class="size-4" />
                            <span>{{ isUploading ? 'Uploading…' : 'Start QA Run' }}</span>
                        </Button>

                        <Button v-if="selectedFile && !isUploading" variant="ghost" @click="clearFile">
                            Clear
                        </Button>
                    </div>
                </div>

                <!-- "What happens next" sidebar -->
                <Card class="h-fit">
                    <CardHeader class="pb-3">
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Sparkles class="text-primary size-4" />
                            What happens next
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ol class="flex flex-col">
                            <li v-for="(step, index) in steps" :key="step.label"
                                class="relative flex gap-3 pb-6 last:pb-0">
                                <!-- Connector line -->
                                <div v-if="index < steps.length - 1"
                                    class="bg-border absolute left-[17px] top-8 h-full w-0.5" />

                                <!-- Step icon -->
                                <div
                                    class="bg-primary/10 relative flex size-9 shrink-0 items-center justify-center rounded-full">
                                    <component :is="step.icon" class="text-primary size-4" />
                                </div>

                                <!-- Step text -->
                                <div class="min-w-0 pt-1">
                                    <p class="text-sm font-semibold">{{ step.label }}</p>
                                    <p class="text-muted-foreground text-xs">{{ step.description }}</p>
                                </div>
                            </li>
                        </ol>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
