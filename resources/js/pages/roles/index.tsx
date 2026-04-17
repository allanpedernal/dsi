import { useEffect, useState, useCallback, useMemo, type ReactNode } from 'react';
import { Head, usePage } from '@inertiajs/react';
import {
    Avatar, Box, Button, Card, CardContent, Checkbox, Chip, CircularProgress,
    Dialog, DialogActions, DialogContent, DialogTitle, Grid, IconButton,
    LinearProgress, Paper, Stack, Table, TableBody, TableCell, TableHead,
    TableRow, TextField, Tooltip, Typography,
} from '@mui/material';
import {
    Edit, Shield, Search, Close, Save as SaveIcon, SelectAll, Deselect,
    AdminPanelSettings, Inventory, Key, Category as CategoryIcon, Lock,
    CheckCircle, Cancel, Add, Delete, TextFields,
} from '@mui/icons-material';
import { toast } from 'sonner';
import { api } from '@/lib/api';
import { useConfirm } from '@/components/confirm-provider';

type Role = { id: number; name: string; permissions: string[]; users_count?: number | null };

const CORE_ACTIONS = ['view', 'create', 'update', 'delete'];

const RESOURCE_ICONS: Record<string, ReactNode> = {
    dashboard: <AdminPanelSettings />,
    customers: <AdminPanelSettings />,
    products: <Inventory />,
    sales: <Inventory />,
    users: <AdminPanelSettings />,
    roles: <Shield />,
    permissions: <Key />,
    reports: <CategoryIcon />,
    audit: <CategoryIcon />,
};

const ROLE_COLORS: Record<string, 'error' | 'warning' | 'success' | 'info' | 'default'> = {
    admin: 'error',
    manager: 'warning',
    cashier: 'success',
    customer: 'info',
};

/** Splits a permission name like "products.view" into ["products", "view"]. */
const parsePerm = (p: string): [string, string] => {
    const idx = p.indexOf('.');
    return idx === -1 ? ['_misc', p] : [p.slice(0, idx), p.slice(idx + 1)];
};

type Matrix = {
    /** All resources in display order. */
    resources: string[];
    /** Core columns present in the system. */
    coreColumns: string[];
    /** `[resource][action] => permission name`, for core actions only. */
    cells: Record<string, Record<string, string>>;
    /** Any action that isn't in CORE_ACTIONS — shown in the "Other" column. */
    extras: Record<string, string[]>;
};

function buildMatrix(allPerms: string[]): Matrix {
    const cells: Record<string, Record<string, string>> = {};
    const extras: Record<string, string[]> = {};
    const coreSet = new Set<string>();

    for (const p of allPerms) {
        const [resource, action] = parsePerm(p);
        if (!cells[resource]) cells[resource] = {};
        if (CORE_ACTIONS.includes(action)) {
            cells[resource][action] = p;
            coreSet.add(action);
        } else {
            (extras[resource] ??= []).push(p);
        }
    }

    const resources = Object.keys(cells).sort((a, b) => {
        if (a === '_misc') return 1;
        if (b === '_misc') return -1;
        return a.localeCompare(b);
    });
    const coreColumns = CORE_ACTIONS.filter((a) => coreSet.has(a));

    return { resources, coreColumns, cells, extras };
}

export default function RolesIndex() {
    const page = usePage<{ auth: { permissions?: string[] } }>();
    const can = (p: string) => (page.props.auth?.permissions ?? []).includes(p);
    const confirm = useConfirm();

    const [roles, setRoles] = useState<Role[]>([]);
    const [allPerms, setAllPerms] = useState<string[]>([]);
    const [protectedRoles, setProtectedRoles] = useState<string[]>([]);
    const [loading, setLoading] = useState(false);

    const [editing, setEditing] = useState<Role | null>(null);
    const [selected, setSelected] = useState<Set<string>>(new Set());
    const [saving, setSaving] = useState(false);
    const [search, setSearch] = useState('');

    const [createOpen, setCreateOpen] = useState(false);
    const [newName, setNewName] = useState('');
    const [newErrors, setNewErrors] = useState<Record<string, string[]>>({});
    const [creating, setCreating] = useState(false);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const res = await api.get<{ data: { roles: Role[]; permissions: string[]; protected: string[] } }>('/roles/data');
            setRoles(res.data.roles);
            setAllPerms(res.data.permissions);
            setProtectedRoles(res.data.protected ?? []);
        } finally { setLoading(false); }
    }, []);

    useEffect(() => { load(); }, [load]);

    const matrix = useMemo(() => buildMatrix(allPerms), [allPerms]);

    const visibleResources = useMemo(() => {
        if (!search.trim()) return matrix.resources;
        const needle = search.toLowerCase();
        return matrix.resources.filter((r) => {
            if (r.toLowerCase().includes(needle)) return true;
            const names = [
                ...Object.values(matrix.cells[r] ?? {}),
                ...(matrix.extras[r] ?? []),
            ];
            return names.some((n) => n.toLowerCase().includes(needle));
        });
    }, [matrix, search]);

    const openEdit = (role: Role) => {
        setEditing(role);
        setSelected(new Set(role.permissions));
        setSearch('');
    };

    const toggle = (perm: string) => {
        setSelected((prev) => {
            const next = new Set(prev);
            if (next.has(perm)) next.delete(perm);
            else next.add(perm);
            return next;
        });
    };

    const toggleMany = (perms: string[], on: boolean) => {
        setSelected((prev) => {
            const next = new Set(prev);
            perms.forEach((p) => (on ? next.add(p) : next.delete(p)));
            return next;
        });
    };

    const resourcePerms = (resource: string): string[] => [
        ...Object.values(matrix.cells[resource] ?? {}),
        ...(matrix.extras[resource] ?? []),
    ];

    const columnPerms = (action: string): string[] =>
        matrix.resources.map((r) => matrix.cells[r]?.[action]).filter((v): v is string => !!v);

    const submit = async () => {
        if (!editing) return;
        setSaving(true);
        try {
            await api.put(`/roles/${editing.id}`, { permissions: Array.from(selected) });
            toast.success(`Role "${editing.name}" permissions updated`);
            setEditing(null);
            load();
        } catch (e) {
            const err = e as { message?: string };
            toast.error(err.message ?? 'Error');
        } finally { setSaving(false); }
    };

    const selectAll = () => toggleMany(allPerms, true);
    const clearAll = () => toggleMany(allPerms, false);

    const isProtected = (name: string) => protectedRoles.includes(name);
    const isLocked = (name: string) => name === 'admin';

    const openCreate = () => {
        setNewName('');
        setNewErrors({});
        setCreateOpen(true);
    };

    const submitCreate = async () => {
        setCreating(true); setNewErrors({});
        try {
            await api.post('/roles', { name: newName.toLowerCase().trim() });
            toast.success(`Role "${newName}" created`);
            setCreateOpen(false);
            load();
        } catch (e) {
            const err = e as { errors?: Record<string, string[]>; message?: string };
            if (err.errors) setNewErrors(err.errors);
            else toast.error(err.message ?? 'Error');
        } finally { setCreating(false); }
    };

    const remove = async (role: Role) => {
        const ok = await confirm({
            title: 'Delete role?',
            description: `"${role.name}" will be removed. Any users assigned to it will lose the role.`,
            confirmText: 'Delete',
            tone: 'error',
        });
        if (!ok) return;
        try {
            await api.delete(`/roles/${role.id}`);
            toast.success(`Role "${role.name}" deleted`);
            load();
        } catch (e) {
            const err = e as { message?: string };
            toast.error(err.message ?? 'Delete failed');
        }
    };

    return (
        <>
            <Head title="Roles & Permissions" />
            <Box sx={{ p: 3 }}>
                <Box sx={{ mb: 2, display: 'flex', alignItems: 'center', gap: 2 }}>
                    <Shield color="primary" />
                    <Typography variant="h5" fontWeight={600}>Roles &amp; Permissions</Typography>
                    <Box sx={{ flexGrow: 1 }} />
                    {can('roles.create') && (
                        <Button startIcon={<Add />} variant="contained" onClick={openCreate}>
                            New Role
                        </Button>
                    )}
                </Box>

                {loading && roles.length === 0 && (
                    <Box sx={{ display: 'flex', justifyContent: 'center', p: 4 }}><CircularProgress /></Box>
                )}

                <Grid container spacing={2}>
                    {roles.map((role) => {
                        const total = allPerms.length || 1;
                        const count = role.permissions.length;
                        const pct = Math.round((count / total) * 100);
                        const color = ROLE_COLORS[role.name] ?? 'default';
                        const locked = isLocked(role.name);
                        const protectedRole = isProtected(role.name);
                        return (
                            <Grid size={{ xs: 12, md: 6 }} key={role.id}>
                                <Card variant="outlined" sx={{ height: '100%' }}>
                                    <CardContent>
                                        <Stack direction="row" alignItems="center" spacing={1.5} sx={{ mb: 1.5 }}>
                                            <Avatar variant="rounded" sx={{ bgcolor: `${color === 'default' ? 'grey' : color}.main`, color: '#fff' }}>
                                                <Shield />
                                            </Avatar>
                                            <Box sx={{ flexGrow: 1 }}>
                                                <Typography variant="h6" fontWeight={600} sx={{ textTransform: 'capitalize', lineHeight: 1.2 }}>
                                                    {role.name}
                                                </Typography>
                                                <Typography variant="caption" color="text.secondary">
                                                    {count} of {total} permissions · {role.users_count ?? 0} user{(role.users_count ?? 0) === 1 ? '' : 's'}
                                                </Typography>
                                            </Box>
                                            {can('roles.update') && !locked && (
                                                <Tooltip title="Edit permissions">
                                                    <IconButton size="small" onClick={() => openEdit(role)}><Edit /></IconButton>
                                                </Tooltip>
                                            )}
                                            {can('roles.delete') && !protectedRole && (() => {
                                                const used = (role.users_count ?? 0) > 0;
                                                return (
                                                    <Tooltip title={used ? `Cannot delete — ${role.users_count} user(s) assigned` : 'Delete role'}>
                                                        <span>
                                                            <IconButton
                                                                size="small"
                                                                color="error"
                                                                onClick={() => remove(role)}
                                                                disabled={used}
                                                            >
                                                                <Delete />
                                                            </IconButton>
                                                        </span>
                                                    </Tooltip>
                                                );
                                            })()}
                                            {locked && (
                                                <Tooltip title="Admin bypasses all checks via Gate::before">
                                                    <Chip icon={<Lock fontSize="small" />} size="small" label="Full access" color="error" />
                                                </Tooltip>
                                            )}
                                        </Stack>

                                        <LinearProgress
                                            variant="determinate"
                                            value={locked ? 100 : pct}
                                            color={color === 'default' ? 'primary' : color}
                                            sx={{ height: 6, borderRadius: 3, mb: 1.5 }}
                                        />

                                        {locked ? (
                                            <Typography variant="body2" color="text.secondary">
                                                Administrator grants access to every action system-wide. Permissions are not editable.
                                            </Typography>
                                        ) : count === 0 ? (
                                            <Typography variant="body2" color="text.secondary">No permissions assigned yet.</Typography>
                                        ) : (
                                            <Box sx={{ display: 'flex', flexWrap: 'wrap', gap: 0.5 }}>
                                                {role.permissions.slice(0, 12).map((p) => (
                                                    <Chip key={p} size="small" label={p} />
                                                ))}
                                                {count > 12 && (
                                                    <Chip size="small" variant="outlined" label={`+${count - 12} more`} />
                                                )}
                                            </Box>
                                        )}
                                    </CardContent>
                                </Card>
                            </Grid>
                        );
                    })}
                </Grid>
            </Box>

            <Dialog open={!!editing} onClose={() => setEditing(null)} fullWidth maxWidth="lg">
                <DialogTitle>
                    <Stack direction="row" spacing={1} alignItems="center">
                        <Edit color="primary" />
                        <Typography variant="h6" sx={{ textTransform: 'capitalize' }}>
                            Edit {editing?.name} permissions
                        </Typography>
                        <Box sx={{ flexGrow: 1 }} />
                        <Chip label={`${selected.size} of ${allPerms.length}`} color="primary" />
                    </Stack>
                </DialogTitle>
                <DialogContent dividers sx={{ p: 0 }}>
                    <Box sx={{ p: 2, display: 'flex', gap: 1.5, alignItems: 'center', borderBottom: 1, borderColor: 'divider' }}>
                        <TextField
                            size="small" placeholder="Filter resources or permissions…"
                            value={search} onChange={(e) => setSearch(e.target.value)}
                            sx={{ flexGrow: 1 }}
                            slotProps={{ input: { startAdornment: <Search fontSize="small" sx={{ mr: 1, color: 'text.secondary' }} /> } }}
                        />
                        <Button startIcon={<SelectAll />} onClick={selectAll}>Select all</Button>
                        <Button startIcon={<Deselect />} onClick={clearAll} color="inherit">Clear</Button>
                    </Box>

                    <Paper elevation={0} sx={{ overflow: 'auto' }}>
                        <Table size="small" stickyHeader>
                            <TableHead>
                                <TableRow>
                                    <TableCell sx={{ bgcolor: 'background.paper', fontWeight: 600, minWidth: 200 }}>Resource</TableCell>
                                    {matrix.coreColumns.map((action) => {
                                        const perms = columnPerms(action);
                                        const allOn = perms.length > 0 && perms.every((p) => selected.has(p));
                                        const someOn = perms.some((p) => selected.has(p));
                                        return (
                                            <TableCell key={action} align="center" sx={{ bgcolor: 'background.paper', fontWeight: 600, textTransform: 'capitalize' }}>
                                                <Stack direction="row" alignItems="center" justifyContent="center" spacing={0.5}>
                                                    <Checkbox
                                                        size="small"
                                                        checked={allOn}
                                                        indeterminate={!allOn && someOn}
                                                        onChange={() => toggleMany(perms, !allOn)}
                                                    />
                                                    <span>{action}</span>
                                                </Stack>
                                            </TableCell>
                                        );
                                    })}
                                    <TableCell sx={{ bgcolor: 'background.paper', fontWeight: 600 }}>Other</TableCell>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {visibleResources.map((resource) => {
                                    const rowPerms = resourcePerms(resource);
                                    const allOn = rowPerms.every((p) => selected.has(p));
                                    const someOn = rowPerms.some((p) => selected.has(p));
                                    const extras = matrix.extras[resource] ?? [];
                                    return (
                                        <TableRow
                                            key={resource}
                                            hover
                                            sx={{ '&:last-child td': { borderBottom: 0 } }}
                                        >
                                            <TableCell>
                                                <Stack direction="row" spacing={1.5} alignItems="center">
                                                    <Checkbox
                                                        size="small"
                                                        checked={allOn}
                                                        indeterminate={!allOn && someOn}
                                                        onChange={() => toggleMany(rowPerms, !allOn)}
                                                    />
                                                    <Avatar variant="rounded" sx={{ bgcolor: 'action.hover', color: 'text.primary', width: 28, height: 28 }}>
                                                        {RESOURCE_ICONS[resource] ?? <Key fontSize="small" />}
                                                    </Avatar>
                                                    <Typography sx={{ textTransform: 'capitalize', fontWeight: 500 }}>
                                                        {resource === '_misc' ? 'Uncategorized' : resource}
                                                    </Typography>
                                                </Stack>
                                            </TableCell>
                                            {matrix.coreColumns.map((action) => {
                                                const perm = matrix.cells[resource]?.[action];
                                                return (
                                                    <TableCell key={action} align="center">
                                                        {perm ? (
                                                            <Checkbox
                                                                size="small"
                                                                checked={selected.has(perm)}
                                                                onChange={() => toggle(perm)}
                                                            />
                                                        ) : (
                                                            <Typography variant="caption" color="text.disabled">—</Typography>
                                                        )}
                                                    </TableCell>
                                                );
                                            })}
                                            <TableCell>
                                                {extras.length === 0 ? (
                                                    <Typography variant="caption" color="text.disabled">—</Typography>
                                                ) : (
                                                    <Stack direction="row" flexWrap="wrap" gap={0.5}>
                                                        {extras.map((p) => {
                                                            const on = selected.has(p);
                                                            const action = p.slice(p.indexOf('.') + 1);
                                                            return (
                                                                <Chip
                                                                    key={p}
                                                                    size="small"
                                                                    label={action}
                                                                    icon={on ? <CheckCircle fontSize="small" /> : <Cancel fontSize="small" />}
                                                                    color={on ? 'primary' : 'default'}
                                                                    variant={on ? 'filled' : 'outlined'}
                                                                    onClick={() => toggle(p)}
                                                                    sx={{ cursor: 'pointer' }}
                                                                />
                                                            );
                                                        })}
                                                    </Stack>
                                                )}
                                            </TableCell>
                                        </TableRow>
                                    );
                                })}
                                {visibleResources.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={matrix.coreColumns.length + 2} align="center">
                                            <Typography variant="body2" color="text.secondary" sx={{ py: 3 }}>
                                                No resources match "{search}".
                                            </Typography>
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </Paper>
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setEditing(null)} startIcon={<Close />}>Cancel</Button>
                    <Button onClick={submit} variant="contained" disabled={saving} startIcon={<SaveIcon />}>
                        Save changes
                    </Button>
                </DialogActions>
            </Dialog>

            <Dialog open={createOpen} onClose={() => setCreateOpen(false)} fullWidth maxWidth="xs">
                <DialogTitle>
                    <Stack direction="row" spacing={1} alignItems="center">
                        <Add color="primary" />
                        <span>New Role</span>
                    </Stack>
                </DialogTitle>
                <DialogContent>
                    <TextField
                        autoFocus fullWidth
                        label="Role name"
                        value={newName}
                        onChange={(e) => setNewName(e.target.value.toLowerCase().trim())}
                        error={!!newErrors.name}
                        helperText={newErrors.name?.[0] ?? 'Lowercase, no spaces (e.g. warehouse, support, accountant)'}
                        sx={{ mt: 1 }}
                        slotProps={{ input: { startAdornment: <TextFields fontSize="small" sx={{ mr: 1, color: 'text.secondary' }} /> } }}
                    />
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => setCreateOpen(false)} startIcon={<Close />}>Cancel</Button>
                    <Button onClick={submitCreate} variant="contained" disabled={creating || !newName} startIcon={<SaveIcon />}>
                        Create
                    </Button>
                </DialogActions>
            </Dialog>
        </>
    );
}
