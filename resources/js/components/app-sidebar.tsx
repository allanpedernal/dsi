import { Link, usePage } from '@inertiajs/react';
import {
    LayoutGrid,
    Users,
    Package,
    ShoppingCart,
    UserCog,
    Shield,
    Key,
    Settings2,
    FileBarChart,
    History,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain, type NavGroup } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';

type SharedAuth = { roles?: string[]; permissions?: string[] };

export function AppSidebar() {
    const page = usePage<{ auth: SharedAuth }>();
    const perms = page.props.auth?.permissions ?? [];
    const can = (p: string) => perms.includes(p);

    const nav: NavGroup[] = [];
    if (can('dashboard.view')) {
        nav.push({ title: 'Dashboard', href: dashboard(), icon: LayoutGrid });
    }
    if (can('customers.view')) nav.push({ title: 'Customers', href: '/customers', icon: Users });
    if (can('products.view')) nav.push({ title: 'Products', href: '/products', icon: Package });
    if (can('sales.view')) nav.push({ title: 'Sales', href: '/sales', icon: ShoppingCart });
    if (can('reports.view')) nav.push({ title: 'Reports', href: '/reports/sales', icon: FileBarChart });
    if (can('audit.view')) nav.push({ title: 'Audit Log', href: '/audit-log', icon: History });

    const systemChildren = [
        can('users.view') && { title: 'Users', href: '/users', icon: UserCog },
        can('roles.view') && { title: 'Roles', href: '/roles', icon: Shield },
        can('permissions.view') && { title: 'Permissions', href: '/permissions', icon: Key },
    ].filter(Boolean) as NonNullable<NavGroup['children']>;

    if (systemChildren.length > 0) {
        nav.push({ title: 'System', href: '#', icon: Settings2, children: systemChildren });
    }

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={nav} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
