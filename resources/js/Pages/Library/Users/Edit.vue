<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue';
import InputError from '@/Components/Library/InputError.vue';
import InputLabel from '@/Components/Library/InputLabel.vue';
import TextInput from '@/Components/Library/TextInput.vue';
import PrimaryButton from '@/Components/Library/PrimaryButton.vue';

const props = defineProps({
    user: Object,
    roles: Array,
});

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    roles: props.user.roles.map(r => r.name),
});

const submit = () => {
    form.patch(route('library.users.update', props.user.id), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const deleteUser = () => {
    if (confirm('Are you sure you want to delete this user? This cannot be undone.')) {
        form.delete(route('library.users.destroy', props.user.id));
    }
};
</script>

<template>
    <Head title="Edit User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-slate-800 dark:text-slate-200 leading-tight">
                    Edit User: {{ user.name }}
                </h2>
                <button @click="deleteUser" class="text-sm font-medium text-red-600 hover:text-red-900">
                    Delete User
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-slate-900 overflow-hidden shadow-sm sm:rounded-lg border border-transparent dark:border-slate-800">
                    <div class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="name" value="Name" />
                                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required autofocus />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="email" value="Email" />
                                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <div class="border-t border-slate-100 dark:border-slate-800 pt-6">
                                <h3 class="text-sm font-medium text-slate-900 dark:text-slate-200 mb-1">Change Password</h3>
                                <p class="text-xs text-slate-500 mb-4">Leave blank to keep current password.</p>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel for="password" value="New Password" />
                                        <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" />
                                        <InputError class="mt-2" :message="form.errors.password" />
                                    </div>
                                    <div>
                                        <InputLabel for="password_confirmation" value="Confirm New Password" />
                                        <TextInput id="password_confirmation" type="password" class="mt-1 block w-full" v-model="form.password_confirmation" />
                                        <InputError class="mt-2" :message="form.errors.password_confirmation" />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <span class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Roles</span>
                                <div class="grid grid-cols-2 gap-2">
                                    <label v-for="role in roles" :key="role.id" class="inline-flex items-center">
                                        <input type="checkbox" :value="role.name" v-model="form.roles" class="rounded border-slate-300 dark:border-slate-700 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                                        <span class="ml-2 text-sm text-slate-600 dark:text-slate-400">{{ role.name.replace('_', ' ') }}</span>
                                    </label>
                                </div>
                                <InputError class="mt-2" :message="form.errors.roles" />
                            </div>

                            <div class="flex items-center justify-end mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                                <Link :href="route('library.users.index')" class="text-sm text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-slate-100 mr-4">
                                    Cancel
                                </Link>
                                <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                                    Update User
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
