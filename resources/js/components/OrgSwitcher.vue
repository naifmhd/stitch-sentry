<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Building2, Check, ChevronsUpDown } from 'lucide-vue-next';
import { computed } from 'vue';
import OrgSwitcherController from '@/actions/App/Http/Controllers/Organizations/OrgSwitcherController';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();

const currentOrganization = computed(() => page.props.currentOrganization);
const organizations = computed(() => page.props.organizations);

const form = useForm<{ organization_id: number | null }>({
    organization_id: null,
});

function switchOrganization(organizationId: number): void {
    if (currentOrganization.value?.id === organizationId) {
        return;
    }

    form.organization_id = organizationId;
    form.submit(OrgSwitcherController());
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="sm" class="flex h-8 items-center gap-1.5 px-2 text-sm font-medium"
                aria-label="Switch organization">
                <Building2 class="h-4 w-4 shrink-0 text-muted-foreground" />
                <span class="max-w-[140px] truncate">
                    {{ currentOrganization?.name ?? 'No organization' }}
                </span>
                <ChevronsUpDown class="h-3.5 w-3.5 shrink-0 text-muted-foreground" />
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="start" class="w-56">
            <DropdownMenuLabel class="text-xs text-muted-foreground">Organizations</DropdownMenuLabel>
            <DropdownMenuSeparator />

            <DropdownMenuItem v-for="org in organizations" :key="org.id" class="cursor-pointer"
                @click="switchOrganization(org.id)">
                <Check class="mr-2 h-4 w-4" :class="org.id === currentOrganization?.id ? 'opacity-100' : 'opacity-0'" />
                <span class="truncate">{{ org.name }}</span>
            </DropdownMenuItem>

            <template v-if="organizations.length === 0">
                <DropdownMenuItem disabled>
                    No organizations found
                </DropdownMenuItem>
            </template>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
