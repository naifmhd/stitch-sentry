<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    CreditCard,
    FolderKanban,
    LayoutGrid,
    Settings,
    Upload,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { billing, dashboard } from '@/routes';
import { edit as profileEdit } from '@/routes/profile';
import { index as uploadIndex } from '@/routes/upload';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Upload',
        href: uploadIndex(),
        icon: Upload,
    },
    {
        title: 'Projects',
        href: '#',
        icon: FolderKanban,
    },
    {
        title: 'Billing',
        href: billing(),
        icon: CreditCard,
    },
    {
        title: 'Settings',
        href: profileEdit(),
        icon: Settings,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
