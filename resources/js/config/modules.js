import { Building2, Search, ShieldCheck, Users, LayoutDashboard } from 'lucide-vue-next';

/**
 * Module registry: the home screen renders from this list (Open/Closed principle).
 * To add a module: set its `route` name here + register the route. No template changes.
 * `route: null` = not built yet (card shown as "coming soon").
 */
export const modules = [
    { key: 'inventory', title: 'Property Inventory', subtitle: 'Property registration and listing', icon: Building2, route: null },
    { key: 'search', title: 'Smart Search', subtitle: 'Advanced filters and matching', icon: Search, route: null },
    { key: 'verification', title: 'Verification', subtitle: 'Legal and document tracking', icon: ShieldCheck, route: null },
    { key: 'owners', title: 'Owner Management', subtitle: 'Restricted owner information', icon: Users, route: null },
];

export const managementModule = {
    key: 'management',
    title: 'Sales & Management Dashboard',
    subtitle: 'Availability · Pricing · Follow-ups · Reports · Performance',
    icon: LayoutDashboard,
    route: null,
};
