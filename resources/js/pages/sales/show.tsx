import { Head, Link } from '@inertiajs/react';
import {
    Box, Button, Card, CardContent, Chip, Divider, Paper, Stack, Table, TableBody,
    TableCell, TableContainer, TableHead, TableRow, Typography,
} from '@mui/material';
import { ArrowBack, Print, Language } from '@mui/icons-material';

type SaleItem = { id: number; product_name: string; product_sku: string; unit_price: number; quantity: number; line_total: number };
type Sale = {
    id: number; reference: string; status: string; status_label: string;
    subtotal: number; tax: number; discount: number; total: number;
    paid_at: string | null; notes: string | null; source: string; created_at: string;
    customer: { id: number; code: string; name: string };
    cashier: { id: number; name: string };
    items: SaleItem[];
};

const fmt = (n: number) =>
    '$' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const statusColor = (s: string): 'success' | 'warning' | 'info' | 'default' =>
    s === 'paid' ? 'success' : s === 'pending' ? 'warning' : s === 'refunded' ? 'info' : 'default';

function Field({ label, primary, secondary }: { label: string; primary: React.ReactNode; secondary?: React.ReactNode }) {
    return (
        <Box>
            <Typography variant="caption" color="text.secondary" sx={{ textTransform: 'uppercase', letterSpacing: 1, fontWeight: 600 }}>
                {label}
            </Typography>
            <Typography variant="body1" fontWeight={500} sx={{ mt: 0.25 }}>
                {primary}
            </Typography>
            {secondary && (
                <Typography variant="body2" color="text.secondary">{secondary}</Typography>
            )}
        </Box>
    );
}

function TotalsRow({ label, value, muted = false, bold = false, big = false }: {
    label: string;
    value: string;
    muted?: boolean;
    bold?: boolean;
    big?: boolean;
}) {
    return (
        <Box sx={{ display: 'flex', alignItems: 'baseline', gap: 3, py: big ? 0.5 : 0 }}>
            <Typography
                variant={big ? 'subtitle1' : 'body2'}
                color={muted ? 'text.secondary' : 'text.primary'}
                fontWeight={bold ? 700 : 400}
            >
                {label}
            </Typography>
            <Box sx={{ flexGrow: 1 }} />
            <Typography
                variant={big ? 'h5' : 'body2'}
                color={big ? 'primary.main' : muted ? 'text.secondary' : 'text.primary'}
                fontWeight={bold ? 700 : 500}
                fontFamily={big ? undefined : 'inherit'}
                sx={{ fontVariantNumeric: 'tabular-nums' }}
            >
                {value}
            </Typography>
        </Box>
    );
}

export default function SaleShow({ sale }: { sale: Sale }) {
    const totalQty = sale.items.reduce((s, i) => s + i.quantity, 0);
    const issued = new Date(sale.created_at);

    return (
        <>
            <Head title={`Sale ${sale.reference}`} />

            <Box sx={{ p: { xs: 2, md: 3 } }} className="print-area">
                {/* Top toolbar */}
                <Stack direction="row" alignItems="center" spacing={1} sx={{ mb: 2 }} className="no-print">
                    <Button component={Link} href="/sales" startIcon={<ArrowBack />} variant="text">
                        Back to sales
                    </Button>
                    <Box sx={{ flexGrow: 1 }} />
                    <Button startIcon={<Print />} variant="outlined" onClick={() => window.print()}>
                        Print
                    </Button>
                </Stack>

                <Card variant="outlined" sx={{ overflow: 'visible' }}>
                    {/* Hero header — bold invoice-style band */}
                    <Box
                        sx={{
                            background: (theme) => `linear-gradient(135deg, ${theme.palette.primary.main} 0%, ${theme.palette.primary.dark} 100%)`,
                            color: 'primary.contrastText',
                            px: { xs: 3, md: 5 },
                            py: { xs: 3, md: 4 },
                            borderBottom: 1,
                            borderColor: 'divider',
                            display: 'flex',
                            flexDirection: { xs: 'column', md: 'row' },
                            alignItems: { xs: 'flex-start', md: 'flex-end' },
                            gap: 2,
                        }}
                        className="print-hero"
                    >
                        <Box>
                            <Typography variant="overline" sx={{ opacity: 0.85, letterSpacing: 2 }}>
                                Sales Receipt
                            </Typography>
                            <Typography variant="h3" fontWeight={800} sx={{ lineHeight: 1, letterSpacing: -1 }}>
                                {sale.reference}
                            </Typography>
                            <Typography variant="body2" sx={{ opacity: 0.9, mt: 0.75 }}>
                                Issued {issued.toLocaleDateString(undefined, { dateStyle: 'long' })}
                                {' · '}
                                {issued.toLocaleTimeString()}
                            </Typography>
                        </Box>
                        <Box sx={{ flexGrow: 1 }} />
                        <Stack direction="row" spacing={1}>
                            <Chip
                                label={sale.status_label}
                                color={statusColor(sale.status)}
                                sx={{ fontWeight: 700, bgcolor: 'common.white', color: (theme) => theme.palette[statusColor(sale.status) === 'default' ? 'grey' : statusColor(sale.status)].main }}
                            />
                            <Chip
                                icon={<Language sx={{ color: 'inherit !important' }} fontSize="small" />}
                                label={sale.source}
                                variant="outlined"
                                sx={{ color: 'primary.contrastText', borderColor: 'rgba(255,255,255,0.5)' }}
                            />
                        </Stack>
                    </Box>

                    <CardContent sx={{ px: { xs: 3, md: 5 }, py: { xs: 3, md: 4 } }}>
                        {/* Parties */}
                        <Box
                            sx={{
                                display: 'grid',
                                gridTemplateColumns: { xs: '1fr', md: 'repeat(4, 1fr)' },
                                gap: 3,
                                mb: 4,
                            }}
                        >
                            <Field label="Bill to" primary={sale.customer.name} secondary={sale.customer.code} />
                            <Field label="Cashier" primary={sale.cashier.name} />
                            <Field
                                label="Date"
                                primary={issued.toLocaleDateString()}
                                secondary={issued.toLocaleTimeString()}
                            />
                            <Field
                                label="Items"
                                primary={`${sale.items.length} line${sale.items.length === 1 ? '' : 's'}`}
                                secondary={`${totalQty} qty total`}
                            />
                        </Box>

                        {/* Items table */}
                        <TableContainer component={Paper} variant="outlined" sx={{ mb: 3 }}>
                            <Table>
                                <TableHead sx={{ bgcolor: 'action.hover' }}>
                                    <TableRow>
                                        <TableCell sx={{ fontWeight: 700, textTransform: 'uppercase', fontSize: 12, letterSpacing: 0.5 }}>Product</TableCell>
                                        <TableCell sx={{ fontWeight: 700, textTransform: 'uppercase', fontSize: 12, letterSpacing: 0.5 }}>SKU</TableCell>
                                        <TableCell align="right" sx={{ fontWeight: 700, textTransform: 'uppercase', fontSize: 12, letterSpacing: 0.5 }}>Unit price</TableCell>
                                        <TableCell align="right" sx={{ fontWeight: 700, textTransform: 'uppercase', fontSize: 12, letterSpacing: 0.5 }}>Qty</TableCell>
                                        <TableCell align="right" sx={{ fontWeight: 700, textTransform: 'uppercase', fontSize: 12, letterSpacing: 0.5 }}>Line total</TableCell>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {sale.items.map((i) => (
                                        <TableRow key={i.id} hover>
                                            <TableCell sx={{ py: 1.5 }}>
                                                <Typography variant="body2" fontWeight={500}>{i.product_name}</Typography>
                                            </TableCell>
                                            <TableCell sx={{ py: 1.5 }}>
                                                <Typography variant="body2" fontFamily="monospace" color="text.secondary">
                                                    {i.product_sku}
                                                </Typography>
                                            </TableCell>
                                            <TableCell align="right" sx={{ py: 1.5, fontVariantNumeric: 'tabular-nums' }}>{fmt(i.unit_price)}</TableCell>
                                            <TableCell align="right" sx={{ py: 1.5 }}>{i.quantity}</TableCell>
                                            <TableCell align="right" sx={{ py: 1.5, fontWeight: 600, fontVariantNumeric: 'tabular-nums' }}>
                                                {fmt(i.line_total)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </TableContainer>

                        {/* Bottom: notes (left) + totals (right) */}
                        <Box
                            sx={{
                                display: 'grid',
                                gridTemplateColumns: { xs: '1fr', md: '1fr 320px' },
                                gap: 4,
                                alignItems: 'flex-start',
                            }}
                        >
                            <Box>
                                <Typography variant="overline" color="text.secondary" sx={{ letterSpacing: 1, fontWeight: 600 }}>
                                    Notes
                                </Typography>
                                <Typography variant="body2" color={sale.notes ? 'text.primary' : 'text.disabled'} sx={{ mt: 0.5, whiteSpace: 'pre-wrap' }}>
                                    {sale.notes || 'No notes on this sale.'}
                                </Typography>
                                {sale.paid_at && (
                                    <Typography variant="caption" color="success.main" sx={{ mt: 2, display: 'block', fontWeight: 600 }}>
                                        ✓ Paid on {new Date(sale.paid_at).toLocaleString()}
                                    </Typography>
                                )}
                            </Box>

                            <Stack spacing={1}>
                                <TotalsRow label="Subtotal" value={fmt(sale.subtotal)} />
                                <TotalsRow label="Tax" value={fmt(sale.tax)} muted={sale.tax === 0} />
                                <TotalsRow label="Discount" value={`-${fmt(sale.discount)}`} muted={sale.discount === 0} />
                                <Divider />
                                <TotalsRow label="Total" value={fmt(sale.total)} bold big />
                            </Stack>
                        </Box>
                    </CardContent>
                </Card>
            </Box>

            <style>{`
                @media print {
                    @page { size: A4 portrait; margin: 12mm; }
                    html, body { background: #fff !important; }
                    .no-print { display: none !important; }

                    /* Let the content use the full page width, no app padding. */
                    .print-area { padding: 0 !important; }
                    .print-area * { box-shadow: none !important; }

                    /* Shrink the hero and tighten overall spacing so everything fits on one page. */
                    .print-hero {
                        background: #3b3f73 !important;
                        -webkit-print-color-adjust: exact;
                        print-color-adjust: exact;
                        padding: 16px 24px !important;
                    }
                    .print-hero h3 { font-size: 22px !important; line-height: 1.1 !important; }
                    .print-hero .MuiChip-root { height: 22px !important; font-size: 11px !important; }

                    /* Compress body padding + typography so it fits on one sheet. */
                    .print-area .MuiCardContent-root { padding: 16px 24px !important; }
                    .print-area .MuiTable-root { font-size: 11px !important; }
                    .print-area .MuiTableCell-root { padding: 6px 8px !important; }
                    .print-area .MuiDivider-root { margin: 6px 0 !important; }
                    .print-area .MuiOutlinedInput-root, .print-area .MuiPaper-outlined { border-color: #cbd5e1 !important; }

                    /* Keep table rows together across any pagination. */
                    table { page-break-inside: avoid; }
                    tr { page-break-inside: avoid; page-break-after: auto; }
                }
            `}</style>
        </>
    );
}
