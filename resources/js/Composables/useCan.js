// resources/js/composables/useCan.js
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

/**
 * Permission composable.
 *
 * Usage in any Vue component:
 *   import { useCan } from '@/composables/useCan'
 *   const can = useCan()
 *
 *   // Template:  v-if="can('approve_transfer')"
 *   // Script:    if (can('create_book')) { ... }
 */
export function useCan() {
    const page = usePage()

    const permissions = computed(
        () => page.props.auth?.permissions ?? []
    )

    const roles = computed(
        () => page.props.auth?.roles ?? []
    )

    /**
     * Check if the current user has a specific permission.
     * @param {string} permission
     * @returns {boolean}
     */
    const can = (permission) => permissions.value.includes(permission)

    /**
     * Check if the current user has a specific role.
     * @param {string} role
     * @returns {boolean}
     */
    const hasRole = (role) => roles.value.includes(role)

    /**
     * Check if the current user has any of the given permissions.
     * @param {string[]} perms
     * @returns {boolean}
     */
    const canAny = (...perms) => perms.some(p => can(p))

    return { can, hasRole, canAny, permissions, roles }
}
