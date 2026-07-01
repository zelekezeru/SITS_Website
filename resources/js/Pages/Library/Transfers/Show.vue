<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/Library/AuthenticatedLayout.vue'

const props = defineProps({ transfer: Object })
const page = usePage()
const permissions = page.props.auth.permissions

const actionNote = ref('')
const courierRef = ref('')

function submitAction(action) {
    let payload = {}
    if (['reject', 'returnToOrigin', 'markLost'].includes(action)) {
        if (!actionNote.value) return alert('Note is required for this action.')
        payload.note = actionNote.value
    }
    if (action === 'dispatch') {
        payload.courier_ref = courierRef.value
    }
    
    let routeName = ''
    if (action === 'approve') routeName = route('library.transfers.approve', props.transfer.id)
    if (action === 'reject') routeName = route('library.transfers.reject', props.transfer.id)
    if (action === 'dispatch') routeName = route('library.transfers.dispatch', props.transfer.id)
    if (action === 'receive') routeName = route('library.transfers.receive', props.transfer.id)
    if (action === 'returnToOrigin') routeName = route('library.transfers.return', props.transfer.id)
    if (action === 'markLost') routeName = route('library.transfers.lost', props.transfer.id)

    router.patch(routeName, payload, {
        onSuccess: () => {
            actionNote.value = ''
            courierRef.value = ''
        }
    })
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-black text-2xl text-gray-900 dark:text-white leading-tight tracking-tight">Transfer #{{ transfer.id }}</h2>
                <div class="px-3 py-1 bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300 rounded-full text-[10px] font-black uppercase tracking-widest border border-indigo-100 dark:border-indigo-800/30">
                    {{ transfer.status }}
                </div>
            </div>
        </template>

        <div class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- Main Info Card -->
            <div class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-800 p-8 grid grid-cols-1 md:grid-cols-2 gap-10 relative overflow-hidden transition-all">
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>
                
                <div class="relative z-10">
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl mb-6 inline-block">
                        <svg class="w-8 h-8 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                    </div>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-white leading-tight mb-2">{{ transfer.copy.book.title }}</h3>
                    <p class="text-xs text-gray-400 dark:text-gray-500 font-mono tracking-tighter uppercase">ID: {{ transfer.copy.tracking_hash.substring(0, 16) }}</p>
                    
                    <div class="mt-8 space-y-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-[10px] font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">Reason / Justification</span>
                            <p class="text-sm text-gray-700 dark:text-gray-300 font-medium italic leading-relaxed">
                                "{{ transfer.reason || 'No specific reason provided for this transfer request.' }}"
                            </p>
                        </div>
                    </div>
                </div>

                <div class="space-y-4 text-sm relative z-10">
                    <div class="bg-gray-50 dark:bg-gray-800/30 rounded-3xl p-6 space-y-3 border border-gray-100 dark:border-gray-800/50">
                        <p class="flex justify-between items-center"><strong class="text-[10px] uppercase tracking-widest text-gray-400">Origin</strong> <span class="font-bold text-gray-900 dark:text-gray-100">{{ transfer.from_campus.name }}</span></p>
                        <p class="flex justify-between items-center"><strong class="text-[10px] uppercase tracking-widest text-gray-400">Destination</strong> <span class="font-bold text-gray-900 dark:text-gray-100">{{ transfer.to_campus.name }}</span></p>
                        <div class="h-px bg-gray-200 dark:bg-gray-700 my-2"></div>
                        <p class="flex justify-between items-center"><strong class="text-[10px] uppercase tracking-widest text-gray-400">Requester</strong> <span class="font-medium text-gray-700 dark:text-gray-300">{{ transfer.requester.name }}</span></p>
                        <p v-if="transfer.approved_by" class="flex justify-between items-center"><strong class="text-[10px] uppercase tracking-widest text-gray-400">Approver</strong> <span class="font-medium text-gray-700 dark:text-gray-300">{{ transfer.approver?.name }}</span></p>
                        <p v-if="transfer.dispatched_by" class="flex justify-between items-center"><strong class="text-[10px] uppercase tracking-widest text-gray-400">Dispatcher</strong> <span class="font-medium text-gray-700 dark:text-gray-300">{{ transfer.dispatcher?.name }}</span></p>
                        <p v-if="transfer.received_by" class="flex justify-between items-center"><strong class="text-[10px] uppercase tracking-widest text-gray-400">Receiver</strong> <span class="font-medium text-gray-700 dark:text-gray-300">{{ transfer.receiver?.name }}</span></p>
                        <p v-if="transfer.courier_ref" class="flex justify-between items-center pt-2"><strong class="text-[10px] uppercase tracking-widest text-indigo-500">Courier Ref</strong> <span class="font-mono text-xs bg-indigo-50 dark:bg-indigo-900/20 px-2 py-1 rounded-lg text-indigo-700 dark:text-indigo-300">{{ transfer.courier_ref }}</span></p>
                    </div>

                    <div v-if="transfer.rejection_note" class="p-6 bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-400 rounded-3xl border border-red-100 dark:border-red-900/30">
                        <strong class="text-[10px] uppercase tracking-widest block mb-2 opacity-70">Administrative Note</strong>
                        <p class="text-sm font-medium">{{ transfer.rejection_note }}</p>
                    </div>
                </div>
            </div>

            <!-- Actions Card -->
            <div v-if="transfer.status === 'requested' || transfer.status === 'approved' || transfer.status === 'in_transit'" class="bg-white dark:bg-gray-900 rounded-[2.5rem] shadow-xl border border-gray-100 dark:border-gray-800 p-8 transition-all">
                <h3 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-[0.2em] mb-8 flex items-center gap-4">
                    Management Actions
                    <div class="h-px flex-1 bg-gray-100 dark:bg-gray-800"></div>
                </h3>
                
                <div v-if="transfer.status === 'requested' && permissions.includes('approve_transfer')" class="space-y-6">
                    <div class="flex flex-wrap gap-4">
                        <button @click="submitAction('approve')" class="px-8 py-3.5 bg-emerald-600 dark:bg-emerald-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-emerald-700 shadow-xl shadow-emerald-500/20 transition-all active:scale-95">Approve Transfer</button>
                    </div>
                    <div class="flex gap-2 max-w-lg">
                        <input v-model="actionNote" placeholder="Reason for rejection..." class="flex-1 rounded-2xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-red-500 transition-all" />
                        <button @click="submitAction('reject')" class="px-8 py-3.5 bg-red-600 dark:bg-red-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-red-700 shadow-xl shadow-red-500/20 transition-all active:scale-95">Reject</button>
                    </div>
                </div>

                <div v-if="transfer.status === 'approved' && permissions.includes('approve_transfer')" class="flex gap-2 max-w-xl">
                    <input v-model="courierRef" placeholder="Courier Tracking / Reference Number..." class="flex-1 rounded-2xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500 transition-all" />
                    <button @click="submitAction('dispatch')" class="px-8 py-3.5 bg-indigo-600 dark:bg-indigo-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-indigo-700 shadow-xl shadow-indigo-500/20 transition-all active:scale-95">Dispatch Package</button>
                </div>

                <div v-if="transfer.status === 'in_transit'" class="space-y-8">
                    <div v-if="permissions.includes('receive_transfer')">
                        <button @click="submitAction('receive')" class="px-8 py-4 bg-emerald-600 dark:bg-emerald-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-emerald-700 shadow-xl shadow-emerald-500/20 transition-all active:scale-95 flex items-center gap-3">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Confirm Receipt at {{ transfer.to_campus.code }}
                        </button>
                    </div>
                    
                    <div v-if="permissions.includes('approve_transfer')" class="pt-8 border-t dark:border-gray-800 space-y-6">
                        <p class="text-[10px] text-gray-400 dark:text-gray-500 uppercase font-black tracking-[0.3em]">Exception Handling</p>
                        <div class="flex flex-wrap gap-3">
                            <input v-model="actionNote" placeholder="Incident report / Note..." class="flex-1 min-w-[300px] rounded-2xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 text-sm focus:ring-2 focus:ring-indigo-500 transition-all" />
                            <button @click="submitAction('returnToOrigin')" class="px-6 py-3.5 bg-amber-500 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-amber-600 shadow-xl shadow-amber-500/20 transition-all active:scale-95">Return</button>
                            <button @click="submitAction('markLost')" class="px-6 py-3.5 bg-red-800 dark:bg-red-700 text-white rounded-2xl text-xs font-black uppercase tracking-widest hover:bg-red-900 shadow-xl shadow-red-900/20 transition-all active:scale-95">Mark Lost</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
