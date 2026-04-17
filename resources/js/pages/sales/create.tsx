import { useEffect, useMemo, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Alert, Autocomplete, Avatar, Box, Button, Card, CardContent, Chip, Divider,
    FormHelperText, Grid, IconButton, InputAdornment, Paper, Stack, Table, TableBody,
    TableCell, TableContainer, TableHead, TableRow, TextField, ToggleButton,
    ToggleButtonGroup, Tooltip, Typography,
} from '@mui/material';
import {
    ArrowBack, AddCircle, Delete, Receipt, Person, ShoppingCart,
    Inventory2, AttachMoney, Percent, Note, Save as SaveIcon, Warning,
} from '@mui/icons-material';
import { toast } from 'sonner';
import { api } from '@/lib/api';

type Customer = { id: number; full_name: string; code: string };
type Product = { id: number; name: string; sku: string; price: number; stock: number };
type LineItem = { product?: Product; quantity: number };

const fmt = (n: number) =>
    '$' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

export default function SalesCreate() {
    const [customers, setCustomers] = useState<Customer[]>([]);
    const [products, setProducts] = useState<Product[]>([]);
    const [customer, setCustomer] = useState<Customer | null>(null);
    const [items, setItems] = useState<LineItem[]>([{ quantity: 1 }]);
    const [taxPct, setTaxPct] = useState('10'); // percent display, e.g. "10" = 10%
    const [discountMode, setDiscountMode] = useState<'amount' | 'percent'>('amount');
    const [discountValue, setDiscountValue] = useState('0');
    const [notes, setNotes] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string[]>>({});

    useEffect(() => {
        api.get<{ data: Customer[] }>('/customers/data?per_page=500').then((r) => setCustomers(r.data));
        api.get<{ data: Product[] }>('/products/data?per_page=500').then((r) => setProducts(r.data));
    }, []);

    // Derived totals
    const subtotal = useMemo(
        () => items.reduce((sum, i) => sum + (i.product?.price ?? 0) * (i.quantity || 0), 0),
        [items],
    );
    const taxRate = (parseFloat(taxPct) || 0) / 100;
    const tax = subtotal * taxRate;
    const discountAmount = useMemo(() => {
        const v = parseFloat(discountValue) || 0;
        return discountMode === 'percent' ? (subtotal + tax) * (v / 100) : v;
    }, [discountMode, discountValue, subtotal, tax]);
    const total = Math.max(0, subtotal + tax - discountAmount);

    const lineCount = items.filter((i) => i.product).length;
    const totalQty = items.reduce((s, i) => s + (i.product ? i.quantity : 0), 0);

    const canSubmit = customer && lineCount > 0 && !submitting;
    const stockBreaches = items
        .map((i, idx) => (i.product && i.quantity > i.product.stock ? { idx, name: i.product.name, have: i.product.stock, want: i.quantity } : null))
        .filter(Boolean) as Array<{ idx: number; name: string; have: number; want: number }>;

    const updateItem = (idx: number, patch: Partial<LineItem>) => {
        setItems((prev) => prev.map((it, i) => (i === idx ? { ...it, ...patch } : it)));
    };
    const addLine = () => setItems((prev) => [...prev, { quantity: 1 }]);
    const removeLine = (idx: number) => setItems((prev) => prev.filter((_, i) => i !== idx));

    const submit = async () => {
        if (!canSubmit) return;
        if (stockBreaches.length > 0) {
            toast.error('Some line items exceed available stock.');
            return;
        }
        setSubmitting(true);
        setErrors({});
        try {
            const payload = {
                customer_id: customer!.id,
                tax_rate: taxRate,
                discount: discountAmount,
                notes: notes || null,
                items: items.filter((i) => i.product).map((i) => ({ product_id: i.product!.id, quantity: i.quantity })),
            };
            const res = await api.post<{ data: { id: number; reference: string } }>('/sales', payload);
            toast.success(`Sale ${res.data.reference} recorded`);
            router.visit(`/sales/${res.data.id}`);
        } catch (e) {
            const err = e as { errors?: Record<string, string[]>; message?: string };
            if (err.errors) setErrors(err.errors);
            else toast.error(err.message ?? 'Error');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <>
            <Head title="New Sale" />

            <Box sx={{ p: 3 }}>
                {/* Top bar */}
                <Stack direction="row" alignItems="center" spacing={1} sx={{ mb: 2 }}>
                    <Button component={Link} href="/sales" startIcon={<ArrowBack />} variant="text">
                        Back to sales
                    </Button>
                    <Box sx={{ flexGrow: 1 }} />
                </Stack>

                <Stack direction="row" alignItems="center" spacing={2} sx={{ mb: 3 }}>
                    <Avatar variant="rounded" sx={{ bgcolor: 'primary.main', width: 48, height: 48 }}>
                        <Receipt />
                    </Avatar>
                    <Box>
                        <Typography variant="overline" color="text.secondary" sx={{ letterSpacing: 1 }}>
                            New transaction
                        </Typography>
                        <Typography variant="h5" fontWeight={700} sx={{ lineHeight: 1.2 }}>
                            Record a sale
                        </Typography>
                    </Box>
                </Stack>

                <Grid container spacing={3}>
                    {/* Main form column */}
                    <Grid size={{ xs: 12, md: 8 }}>
                        <Stack spacing={3}>
                            {/* Customer */}
                            <Card variant="outlined">
                                <CardContent>
                                    <SectionHeader icon={<Person color="primary" />} title="Customer" step="1" />
                                    <Autocomplete
                                        size="small"
                                        options={customers}
                                        getOptionLabel={(o) => `${o.full_name} (${o.code})`}
                                        value={customer}
                                        onChange={(_, v) => setCustomer(v)}
                                        isOptionEqualToValue={(a, b) => a.id === b.id}
                                        renderInput={(params) => (
                                            <TextField
                                                {...params}
                                                size="small"
                                                label="Select customer"
                                                required
                                                error={!!errors.customer_id}
                                                helperText={errors.customer_id?.[0] ?? 'Search by name or customer code.'}
                                            />
                                        )}
                                    />
                                </CardContent>
                            </Card>

                            {/* Line items */}
                            <Card variant="outlined">
                                <CardContent>
                                    <Stack direction="row" alignItems="center" spacing={1.5} sx={{ mb: 2 }}>
                                        <ShoppingCart color="primary" />
                                        <Typography variant="subtitle1" fontWeight={600}>Line items</Typography>
                                        <Chip size="small" label="Step 2" />
                                        <Box sx={{ flexGrow: 1 }} />
                                        <Button
                                            startIcon={<AddCircle />}
                                            variant="outlined"
                                            size="small"
                                            onClick={addLine}
                                        >
                                            Add line
                                        </Button>
                                    </Stack>

                                    {stockBreaches.length > 0 && (
                                        <Alert severity="warning" icon={<Warning />} sx={{ mb: 2 }}>
                                            {stockBreaches.map((b) => (
                                                <div key={b.idx}>
                                                    <strong>{b.name}</strong> — wants {b.want}, only {b.have} in stock.
                                                </div>
                                            ))}
                                        </Alert>
                                    )}

                                    <TableContainer component={Paper} variant="outlined" sx={{ overflow: 'visible' }}>
                                        <Table size="small">
                                            <TableHead sx={{ bgcolor: 'action.hover' }}>
                                                <TableRow>
                                                    <TableCell sx={{ fontWeight: 600, minWidth: 260 }}>Product</TableCell>
                                                    <TableCell align="right" sx={{ fontWeight: 600 }}>Unit price</TableCell>
                                                    <TableCell align="right" sx={{ fontWeight: 600, width: 110 }}>Qty</TableCell>
                                                    <TableCell align="right" sx={{ fontWeight: 600 }}>Stock</TableCell>
                                                    <TableCell align="right" sx={{ fontWeight: 600 }}>Line total</TableCell>
                                                    <TableCell width={48} />
                                                </TableRow>
                                            </TableHead>
                                            <TableBody>
                                                {items.map((it, idx) => {
                                                    const over = !!(it.product && it.quantity > it.product.stock);
                                                    return (
                                                        <TableRow key={idx} hover>
                                                            <TableCell>
                                                                <Autocomplete
                                                                    fullWidth
                                                                    size="small"
                                                                    options={products}
                                                                    getOptionLabel={(o) => `${o.name} (${o.sku})`}
                                                                    value={it.product ?? null}
                                                                    isOptionEqualToValue={(a, b) => a.id === b.id}
                                                                    onChange={(_, v) => updateItem(idx, { product: v ?? undefined })}
                                                                    renderOption={(props, o) => (
                                                                        <li {...props} key={o.id}>
                                                                            <Stack sx={{ width: '100%' }}>
                                                                                <Stack direction="row" justifyContent="space-between" alignItems="center">
                                                                                    <Typography variant="body2" fontWeight={500}>{o.name}</Typography>
                                                                                    <Typography variant="body2" color="primary">{fmt(o.price)}</Typography>
                                                                                </Stack>
                                                                                <Stack direction="row" justifyContent="space-between">
                                                                                    <Typography variant="caption" color="text.secondary" fontFamily="monospace">{o.sku}</Typography>
                                                                                    <Typography variant="caption" color={o.stock <= 5 ? 'warning.main' : 'text.secondary'}>
                                                                                        {o.stock} in stock
                                                                                    </Typography>
                                                                                </Stack>
                                                                            </Stack>
                                                                        </li>
                                                                    )}
                                                                    renderInput={(params) => (
                                                                        <TextField
                                                                            {...params}
                                                                            placeholder="Search products…"
                                                                            variant="standard"
                                                                        />
                                                                    )}
                                                                />
                                                            </TableCell>
                                                            <TableCell align="right">
                                                                {it.product ? fmt(it.product.price) : <Typography variant="caption" color="text.disabled">—</Typography>}
                                                            </TableCell>
                                                            <TableCell align="right">
                                                                <TextField
                                                                    type="number"
                                                                    size="small"
                                                                    variant="standard"
                                                                    value={it.quantity}
                                                                    error={over}
                                                                    onChange={(e) => updateItem(idx, { quantity: Math.max(0, parseInt(e.target.value) || 0) })}
                                                                    slotProps={{ htmlInput: { min: 0, max: it.product?.stock, style: { textAlign: 'right' } } }}
                                                                    sx={{ width: 80 }}
                                                                />
                                                            </TableCell>
                                                            <TableCell align="right">
                                                                {it.product ? (
                                                                    <Chip
                                                                        size="small"
                                                                        label={it.product.stock}
                                                                        color={it.product.stock <= 5 ? 'warning' : 'default'}
                                                                        variant="outlined"
                                                                    />
                                                                ) : <Typography variant="caption" color="text.disabled">—</Typography>}
                                                            </TableCell>
                                                            <TableCell align="right" sx={{ fontWeight: 500 }}>
                                                                {fmt((it.product?.price ?? 0) * (it.quantity || 0))}
                                                            </TableCell>
                                                            <TableCell align="right">
                                                                <Tooltip title={items.length === 1 ? 'At least one line required' : 'Remove line'}>
                                                                    <span>
                                                                        <IconButton
                                                                            size="small"
                                                                            color="error"
                                                                            onClick={() => removeLine(idx)}
                                                                            disabled={items.length === 1}
                                                                        >
                                                                            <Delete fontSize="small" />
                                                                        </IconButton>
                                                                    </span>
                                                                </Tooltip>
                                                            </TableCell>
                                                        </TableRow>
                                                    );
                                                })}
                                                {items.length === 0 && (
                                                    <TableRow>
                                                        <TableCell colSpan={6} align="center">
                                                            <Stack spacing={1} alignItems="center" sx={{ py: 3 }}>
                                                                <Inventory2 color="disabled" />
                                                                <Typography variant="body2" color="text.secondary">
                                                                    No line items yet.
                                                                </Typography>
                                                                <Button onClick={addLine} startIcon={<AddCircle />}>Add first line</Button>
                                                            </Stack>
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </TableBody>
                                        </Table>
                                    </TableContainer>
                                </CardContent>
                            </Card>

                            {/* Adjustments */}
                            <Card variant="outlined">
                                <CardContent>
                                    <SectionHeader icon={<AttachMoney color="primary" />} title="Adjustments" step="3" />
                                    <Stack direction={{ xs: 'column', md: 'row' }} spacing={2} alignItems="flex-start">
                                        <TextField
                                            size="small"
                                            label="Tax"
                                            type="number"
                                            value={taxPct}
                                            onChange={(e) => setTaxPct(e.target.value)}
                                            sx={{ width: 140 }}
                                            slotProps={{
                                                input: { endAdornment: <InputAdornment position="end"><Percent fontSize="small" /></InputAdornment> },
                                                htmlInput: { min: 0, step: 0.5 },
                                            }}
                                            helperText="e.g. 10 = 10%"
                                        />
                                        <Box sx={{ width: 260 }}>
                                            <Stack direction="row" spacing={1}>
                                                <TextField
                                                    size="small"
                                                    label="Discount"
                                                    type="number"
                                                    value={discountValue}
                                                    onChange={(e) => setDiscountValue(e.target.value)}
                                                    fullWidth
                                                    slotProps={{
                                                        input: {
                                                            startAdornment: discountMode === 'amount'
                                                                ? <InputAdornment position="start">$</InputAdornment>
                                                                : undefined,
                                                            endAdornment: discountMode === 'percent'
                                                                ? <InputAdornment position="end"><Percent fontSize="small" /></InputAdornment>
                                                                : undefined,
                                                        },
                                                        htmlInput: { min: 0, step: 0.5 },
                                                    }}
                                                />
                                                <ToggleButtonGroup
                                                    size="small"
                                                    exclusive
                                                    value={discountMode}
                                                    onChange={(_, v: 'amount' | 'percent' | null) => v && setDiscountMode(v)}
                                                    aria-label="Discount mode"
                                                >
                                                    <ToggleButton value="amount" aria-label="Amount">$</ToggleButton>
                                                    <ToggleButton value="percent" aria-label="Percent"><Percent fontSize="small" /></ToggleButton>
                                                </ToggleButtonGroup>
                                            </Stack>
                                            <FormHelperText>
                                                Applied {discountMode === 'amount' ? 'as a flat amount' : 'as a percent of subtotal + tax'}.
                                            </FormHelperText>
                                        </Box>
                                        <TextField
                                            size="small"
                                            label="Notes"
                                            value={notes}
                                            onChange={(e) => setNotes(e.target.value)}
                                            multiline
                                            minRows={1}
                                            sx={{ flex: 1 }}
                                            slotProps={{
                                                input: { startAdornment: <InputAdornment position="start"><Note fontSize="small" /></InputAdornment> },
                                            }}
                                            placeholder="Optional — e.g. customer instructions, PO number…"
                                        />
                                    </Stack>
                                </CardContent>
                            </Card>
                        </Stack>
                    </Grid>

                    {/* Sticky summary column */}
                    <Grid size={{ xs: 12, md: 4 }}>
                        <Box sx={{ position: { md: 'sticky' }, top: { md: 24 } }}>
                            <Card variant="outlined">
                                <CardContent>
                                    <Typography variant="overline" color="text.secondary">Order Summary</Typography>

                                    {customer ? (
                                        <Stack direction="row" spacing={1} alignItems="center" sx={{ mt: 1, mb: 2 }}>
                                            <Avatar sx={{ bgcolor: 'primary.light', width: 32, height: 32 }}>
                                                <Person fontSize="small" />
                                            </Avatar>
                                            <Box sx={{ minWidth: 0 }}>
                                                <Typography variant="body2" fontWeight={500} noWrap>{customer.full_name}</Typography>
                                                <Typography variant="caption" color="text.secondary">{customer.code}</Typography>
                                            </Box>
                                        </Stack>
                                    ) : (
                                        <Typography variant="body2" color="text.secondary" sx={{ mt: 1, mb: 2 }}>
                                            No customer selected yet.
                                        </Typography>
                                    )}

                                    <Divider sx={{ mb: 2 }} />

                                    <Stack spacing={1}>
                                        <SummaryRow label={`Items (${lineCount})`} value={`${totalQty} qty`} muted />
                                        <SummaryRow label="Subtotal" value={fmt(subtotal)} />
                                        <SummaryRow
                                            label={`Tax (${taxPct || 0}%)`}
                                            value={fmt(tax)}
                                            muted={tax === 0}
                                        />
                                        <SummaryRow
                                            label={`Discount${discountMode === 'percent' ? ` (${discountValue || 0}%)` : ''}`}
                                            value={`-${fmt(discountAmount)}`}
                                            muted={discountAmount === 0}
                                        />
                                    </Stack>

                                    <Divider sx={{ my: 2 }} />

                                    <Box sx={{ display: 'flex', width: '100%', alignItems: 'center', gap: 2 }}>
                                        <Typography variant="subtitle1" fontWeight={700}>Total</Typography>
                                        <Box sx={{ flexGrow: 1 }} />
                                        <Typography variant="h4" fontWeight={700} color="primary.main">
                                            {fmt(total)}
                                        </Typography>
                                    </Box>

                                    <Button
                                        variant="contained"
                                        size="large"
                                        fullWidth
                                        sx={{ mt: 2 }}
                                        disabled={!canSubmit || stockBreaches.length > 0}
                                        onClick={submit}
                                        startIcon={<SaveIcon />}
                                    >
                                        Record sale
                                    </Button>

                                    {!customer && (
                                        <Typography variant="caption" color="text.secondary" sx={{ mt: 1, display: 'block', textAlign: 'center' }}>
                                            Select a customer to continue.
                                        </Typography>
                                    )}
                                    {customer && lineCount === 0 && (
                                        <Typography variant="caption" color="text.secondary" sx={{ mt: 1, display: 'block', textAlign: 'center' }}>
                                            Add at least one product.
                                        </Typography>
                                    )}

                                    <Button
                                        component={Link}
                                        href="/sales"
                                        variant="text"
                                        fullWidth
                                        size="small"
                                        sx={{ mt: 1 }}
                                    >
                                        Cancel
                                    </Button>
                                </CardContent>
                            </Card>
                        </Box>
                    </Grid>
                </Grid>
            </Box>
        </>
    );
}

function SectionHeader({ icon, title, step }: { icon: React.ReactNode; title: string; step: string }) {
    return (
        <Stack direction="row" alignItems="center" spacing={1.5} sx={{ mb: 2 }}>
            {icon}
            <Typography variant="subtitle1" fontWeight={600}>{title}</Typography>
            <Chip size="small" label={`Step ${step}`} />
        </Stack>
    );
}

function SummaryRow({ label, value, muted = false }: { label: string; value: string; muted?: boolean }) {
    return (
        <Box sx={{ display: 'flex', width: '100%', alignItems: 'baseline', gap: 2 }}>
            <Typography variant="body2" color={muted ? 'text.secondary' : 'text.primary'}>{label}</Typography>
            <Box sx={{ flexGrow: 1 }} />
            <Typography variant="body2" color={muted ? 'text.secondary' : 'text.primary'} fontWeight={muted ? 400 : 500}>
                {value}
            </Typography>
        </Box>
    );
}
