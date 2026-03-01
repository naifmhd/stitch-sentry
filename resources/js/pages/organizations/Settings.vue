<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { UserPlus } from 'lucide-vue-next';
import { show as showOrgSettings } from '@/actions/App/Http/Controllers/Organizations/OrgSettingsController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';

type OrgMember = {
    id: number;
    name: string;
    email: string;
    role: string;
};

type OrganizationData = {
    id: number;
    name: string;
};

type Props = {
    organization: OrganizationData;
    members: OrgMember[];
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Organization Settings', href: showOrgSettings(props.organization.id) },
];

const roleVariant = (role: string): 'default' | 'secondary' | 'outline' => {
    if (role === 'owner') {
        return 'default';
    }

    if (role === 'admin') {
        return 'secondary';
    }

    return 'outline';
};
</script>

<template>

    <Head :title="`${organization.name} — Settings`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Organization Settings</h1>
                    <p class="text-muted-foreground">{{ organization.name }}</p>
                </div>

                <Button variant="outline" disabled>
                    <UserPlus class="mr-2 h-4 w-4" />
                    Invite Member
                </Button>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Members</CardTitle>
                    <CardDescription>
                        People who have access to this organization.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="divide-y">
                        <div v-for="member in members" :key="member.id" class="flex items-center justify-between py-3">
                            <div class="flex flex-col">
                                <span class="font-medium">{{ member.name }}</span>
                                <span class="text-sm text-muted-foreground">{{ member.email }}</span>
                            </div>
                            <Badge :variant="roleVariant(member.role)" class="capitalize">
                                {{ member.role }}
                            </Badge>
                        </div>

                        <div v-if="members.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                            No members found.
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
