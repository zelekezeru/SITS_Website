<script setup>
import { Head, useForm, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'

const props = defineProps({
    resource: Object,
    permissions: Array,
    roles: Array
})

const form = useForm({
    name: props.resource.name ?? '',
    url: props.resource.url ?? '',
    category: props.resource.category ?? '',
    provider: props.resource.provider ?? '',
    description: props.resource.description ?? '',
    logo_path: props.resource.logo_path ?? '',
    access_tier: props.resource.access_tier ?? 'free',
    required_permission: props.resource.required_permission ?? '',
    allowed_roles: props.resource.allowed_roles ?? [],
    is_active: props.resource.is_active ?? true,
    sort_order: props.resource.sort_order ?? 0,
})

const submit = () => {
    if (props.resource.id) {
        form.put(route('library.admin.resources.update', props.resource.id))
    } else {
        form.post(route('library.admin.resources.store'))
    }
}
</script>

<template>
    <Head :title="resource.id ? 'Edit Resource' : 'Add Resource'" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ resource.id ? 'Edit Resource' : 'Add New External Resource' }}
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-6 border border-transparent dark:border-gray-800">
                    <div class="grid grid-cols-2 gap-6">
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Resource Name</label>
                            <input v-model="form.name" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Provider Name</label>
                            <input v-model="form.provider" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Resource URL</label>
                            <input v-model="form.url" type="url" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="https://" required>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Category</label>
                            <input v-model="form.category" type="text" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Sort Order</label>
                            <input v-model="form.sort_order" type="number" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Description</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800">
                        <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 mb-4 uppercase tracking-wider">Access Controls</h3>
                        
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Access Tier</label>
                                <select v-model="form.access_tier" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="free">Free (All users)</option>
                                    <option value="premium">Premium (Requires specific permission)</option>
                                    <option value="restricted">Restricted (Explicit role check)</option>
                                </select>
                            </div>
                            <div v-if="form.access_tier !== 'free'">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-400">Required Permission</label>
                                <select v-model="form.required_permission" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-950 dark:text-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">None</option>
                                    <option v-for="p in permissions" :key="p" :value="p">{{ p }}</option>
                                </select>
                            </div>
                        </div>

                        <div v-if="form.access_tier === 'restricted'" class="mt-6">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-400 mb-2">Allowed Roles</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label v-for="role in roles" :key="role" class="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                                    <input type="checkbox" v-model="form.allowed_roles" :value="role" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-950">
                                    <span>{{ role }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <label class="flex items-center space-x-3 cursor-pointer">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-950">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Is Active</span>
                        </label>
                        
                        <div class="flex items-center space-x-3">
                            <Link :href="route('library.admin.resources.index')" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 transition">Cancel</Link>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" :disabled="form.processing">
                                {{ resource.id ? 'Update Resource' : 'Create Resource' }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
