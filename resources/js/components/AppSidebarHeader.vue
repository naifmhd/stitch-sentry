<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Coins } from 'lucide-vue-next';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import OrgSwitcher from '@/components/OrgSwitcher.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const creditsBalance = computed(() => page.props.creditsBalance);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
        <div class="flex flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="flex items-center gap-3">
            <span
                v-if="creditsBalance !== null && creditsBalance !== undefined"
                class="text-muted-foreground flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium"
                title="Available credits"
            >
                <Coins class="size-3" />
                {{ creditsBalance.toLocaleString() }}
            </span>
            <OrgSwitcher />
        </div>
    </header>
</template>
