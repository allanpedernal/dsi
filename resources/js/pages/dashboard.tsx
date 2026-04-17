import { Head, router } from '@inertiajs/react';
import { Box, Card, CardContent, Chip, Divider, LinearProgress, Typography } from '@mui/material';
import { LineChart } from '@mui/x-charts/LineChart';
import { BarChart } from '@mui/x-charts/BarChart';
import { dashboard } from '@/routes';
import type { ReactNode } from 'react';
import { CustomerFilter, type CustomerOption } from '@/components/customer-filter';

type Trend = { date: string; revenue: number; orders: number };
type TopProduct = { name: string; sku: string; units: number; revenue: number };
type LowStock = { id: number; name: string; sku: string; stock: number; reorder_level: number };
type Activity = { id: number; description: string; source: string | null; causer: string | null; created_at: string };

type Props = {
    today: { count: number; revenue: number; aov: number };
    month_revenue: number;
    prev_month_revenue: number;
    month_orders: number;
    avg_order_value: number;
    trend: Trend[];
    top_products: TopProduct[];
    low_stock: LowStock[];
    recent_activity: Activity[];
    filter: { customer_id: number | null; locked: boolean };
    customers: CustomerOption[];
};

const money = (n: number) => '$' + n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const compact = (n: number) => {
    if (Math.abs(n) >= 1e6) return '$' + (n / 1e6).toFixed(1) + 'M';
    if (Math.abs(n) >= 1e3) return '$' + (n / 1e3).toFixed(1) + 'k';
    return money(n);
};

/* ─── Tiny building blocks ──────────────────────────────────────────── */

function Stat({ label, value, sub, delta }: { label: string; value: string; sub?: string; delta?: number | null }) {
    return (
        <Card variant="outlined" sx={{ height: '100%' }}>
            <CardContent sx={{ p: 2.5, '&:last-child': { pb: 2.5 } }}>
                <Typography variant="caption" sx={{ color: 'text.secondary', textTransform: 'uppercase', letterSpacing: 0.8, fontWeight: 600, fontSize: 11 }}>
                    {label}
                </Typography>
                <Typography variant="h4" sx={{ fontWeight: 700, mt: 0.5, lineHeight: 1, fontVariantNumeric: 'tabular-nums' }}>
                    {value}
                </Typography>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1, mt: 1.25, minHeight: 20 }}>
                    {delta != null && (
                        <Chip
                            size="small"
                            label={`${delta >= 0 ? '↑' : '↓'} ${Math.abs(delta).toFixed(1)}%`}
                            sx={{ height: 20, fontWeight: 700, fontSize: 11, bgcolor: delta >= 0 ? 'success.main' : 'error.main', color: '#fff', '& .MuiChip-label': { px: 0.75 } }}
                        />
                    )}
                    {sub && <Typography variant="caption" color="text.secondary">{sub}</Typography>}
                </Box>
            </CardContent>
        </Card>
    );
}

function Panel({ title, right, children, noPad }: { title: string; right?: ReactNode; children: ReactNode; noPad?: boolean }) {
    return (
        <Card variant="outlined" sx={{ /* height: auto */ display: 'flex', flexDirection: 'column' }}>
            <Box sx={{ px: 2.5, py: 1.5, display: 'flex', alignItems: 'center', borderBottom: 1, borderColor: 'divider' }}>
                <Typography variant="subtitle2" fontWeight={700} sx={{ fontSize: 13 }}>{title}</Typography>
                <Box sx={{ flexGrow: 1 }} />
                {right}
            </Box>
            <Box sx={{ flexGrow: 1, ...(noPad ? {} : { p: 2.5 }) }}>
                {children}
            </Box>
        </Card>
    );
}

function Row({ left, sub, right, rightSub, dot }: { left: ReactNode; sub?: ReactNode; right?: ReactNode; rightSub?: ReactNode; dot?: string }) {
    return (
        <Box sx={{ px: 2.5, py: 1.25, display: 'flex', alignItems: 'center', gap: 1.5, '&:not(:last-of-type)': { borderBottom: 1, borderColor: 'divider' } }}>
            {dot && <Box sx={{ width: 8, height: 8, borderRadius: '50%', bgcolor: dot, flexShrink: 0 }} />}
            <Box sx={{ minWidth: 0, flexGrow: 1 }}>
                <Typography variant="body2" fontWeight={500} noWrap>{left}</Typography>
                {sub && <Typography variant="caption" color="text.secondary" noWrap component="div">{sub}</Typography>}
            </Box>
            {(right || rightSub) && (
                <Box sx={{ textAlign: 'right', flexShrink: 0 }}>
                    {typeof right === 'string' || typeof right === 'number'
                        ? <Typography variant="body2" fontWeight={600} sx={{ fontVariantNumeric: 'tabular-nums' }}>{right}</Typography>
                        : right}
                    {rightSub && <Typography variant="caption" color="text.secondary" component="div">{rightSub}</Typography>}
                </Box>
            )}
        </Box>
    );
}

function Empty({ text, height = 200 }: { text: string; height?: number }) {
    return (
        <Box sx={{ height, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <Typography variant="body2" color="text.disabled">{text}</Typography>
        </Box>
    );
}

/* ─── Page ───────────────────────────────────────────────────────────── */

export default function Dashboard({
    today, month_revenue, prev_month_revenue, month_orders, avg_order_value,
    trend, top_products, low_stock, recent_activity, filter, customers,
}: Props) {
    const setCustomer = (id: number | null) => {
        router.get(dashboard(), id ? { customer_id: id } : {}, { preserveState: true, preserveScroll: true, replace: true });
    };

    const dates = trend.map((t) => t.date.slice(5));
    const rev = trend.map((t) => t.revenue);
    const ord = trend.map((t) => t.orders);
    const totalOrd = ord.reduce((a, b) => a + b, 0);
    const delta = prev_month_revenue > 0 ? ((month_revenue - prev_month_revenue) / prev_month_revenue) * 100 : month_revenue > 0 ? 100 : 0;

    return (
        <>
            <Head title="Dashboard" />
            <Box sx={{ p: 3 }}>

                {/* Header */}
                <Box sx={{ display: 'flex', alignItems: 'center', flexWrap: 'wrap', gap: 2, mb: 3 }}>
                    <Box sx={{ flexGrow: 1 }}>
                        <Typography variant="h5" fontWeight={700}>Overview</Typography>
                        <Typography variant="body2" color="text.secondary">Last 30 days · {totalOrd} orders</Typography>
                    </Box>
                    <CustomerFilter options={customers} value={filter.customer_id} onChange={setCustomer} locked={filter.locked} />
                </Box>

                {/* KPIs */}
                <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr 1fr', md: 'repeat(4,1fr)' }, gap: 2, mb: 3 }}>
                    <Stat label="Today" value={money(today.revenue)} sub={`${today.count} order${today.count === 1 ? '' : 's'}`} />
                    <Stat label="30-day revenue" value={compact(month_revenue)} delta={delta} sub={`vs ${compact(prev_month_revenue)} prev`} />
                    <Stat label="Avg. order" value={money(avg_order_value)} sub={`${month_orders} orders`} />
                    <Stat label="Low stock" value={String(low_stock.length)} sub={low_stock.length ? 'Needs attention' : 'All healthy'} />
                </Box>

                {/* Charts */}
                <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', lg: '2fr 1fr' }, gap: 2, mb: 3 }}>
                    <Panel title="Revenue" right={<Typography variant="caption" color="text.secondary">30 days</Typography>}>
                        {rev.some((r) => r > 0) ? (
                            <LineChart
                                height={280}
                                margin={{ top: 8, right: 8, bottom: 24, left: 52 }}
                                xAxis={[{ scaleType: 'point', data: dates, tickLabelStyle: { fontSize: 10 } }]}
                                yAxis={[{ valueFormatter: (v: number) => compact(v), tickLabelStyle: { fontSize: 10 } }]}
                                series={[{ data: rev, curve: 'monotoneX', area: true, showMark: false, valueFormatter: (v) => money(Number(v ?? 0)) }]}
                                grid={{ horizontal: true }}
                                slotProps={{ legend: { sx: { display: 'none' } } }}
                            />
                        ) : <Empty text="No revenue data yet." height={280} />}
                    </Panel>
                    <Panel title="Orders" right={<Typography variant="caption" color="text.secondary">30 days</Typography>}>
                        {ord.some((o) => o > 0) ? (
                            <BarChart
                                height={280}
                                margin={{ top: 8, right: 8, bottom: 24, left: 28 }}
                                xAxis={[{ scaleType: 'band', data: dates, tickLabelStyle: { fontSize: 10 } }]}
                                yAxis={[{ tickLabelStyle: { fontSize: 10 } }]}
                                series={[{ data: ord }]}
                                grid={{ horizontal: true }}
                                slotProps={{ legend: { sx: { display: 'none' } } }}
                            />
                        ) : <Empty text="No order data yet." height={280} />}
                    </Panel>
                </Box>

                {/* Lists */}
                <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: '1fr 1fr' }, gap: 2, mb: 3 }}>
                    <Panel title="Top products" right={<Typography variant="caption" color="text.secondary">30 days</Typography>} noPad>
                        {top_products.length === 0 ? <Empty text="No sales yet." height={240} /> : (
                            top_products.map((p, i) => {
                                const pct = (p.units / (top_products[0]?.units || 1)) * 100;
                                return (
                                    <Box key={p.sku} sx={{ px: 2.5, py: 1.5, '&:not(:last-of-type)': { borderBottom: 1, borderColor: 'divider' } }}>
                                        <Box sx={{ display: 'flex', alignItems: 'center', gap: 1.5, mb: 0.75 }}>
                                            <Typography variant="caption" sx={{ width: 18, textAlign: 'center', fontWeight: 700, color: 'text.secondary' }}>{i + 1}</Typography>
                                            <Box sx={{ flexGrow: 1, minWidth: 0 }}>
                                                <Typography variant="body2" fontWeight={500} noWrap>{p.name}</Typography>
                                                <Typography variant="caption" color="text.secondary" fontFamily="monospace">{p.sku}</Typography>
                                            </Box>
                                            <Box sx={{ textAlign: 'right' }}>
                                                <Typography variant="body2" fontWeight={600} sx={{ fontVariantNumeric: 'tabular-nums' }}>{money(p.revenue)}</Typography>
                                                <Typography variant="caption" color="text.secondary">{p.units} units</Typography>
                                            </Box>
                                        </Box>
                                        <LinearProgress variant="determinate" value={pct} sx={{ height: 3, borderRadius: 2, ml: 3.5 }} />
                                    </Box>
                                );
                            })
                        )}
                    </Panel>

                    <Panel
                        title="Low stock"
                        right={low_stock.length > 0 ? <Chip size="small" label={low_stock.length} color="warning" sx={{ height: 20, fontWeight: 700, '& .MuiChip-label': { px: 0.75 } }} /> : undefined}
                        noPad
                    >
                        {low_stock.length === 0 ? <Empty text="All products well-stocked." height={240} /> : (
                            low_stock.map((p) => (
                                <Row
                                    key={p.id}
                                    dot={p.stock === 0 ? 'error.main' : 'warning.main'}
                                    left={p.name}
                                    sub={`${p.sku} · reorder at ${p.reorder_level}`}
                                    right={<Chip size="small" color={p.stock === 0 ? 'error' : 'warning'} label={p.stock === 0 ? 'Out' : `${p.stock}`} sx={{ height: 20, fontWeight: 700, '& .MuiChip-label': { px: 0.75 } }} />}
                                />
                            ))
                        )}
                    </Panel>
                </Box>

                {/* Activity — hidden for customer users */}
                {!filter.locked && (
                    <Panel title="Recent activity" right={<Typography variant="caption" color="text.secondary">{recent_activity.length} events</Typography>} noPad>
                        {recent_activity.length === 0 ? <Empty text="No activity yet." height={160} /> : (
                            recent_activity.map((a) => (
                                <Row
                                    key={a.id}
                                    dot={a.source === 'web' ? 'primary.main' : a.source === 'api' ? 'secondary.main' : 'grey.400'}
                                    left={a.description}
                                    sub={`${a.causer ?? 'System'} · ${a.created_at}`}
                                    right={<Typography variant="caption" color="text.secondary" sx={{ textTransform: 'capitalize' }}>{a.source ?? 'system'}</Typography>}
                                />
                            ))
                        )}
                    </Panel>
                )}

                <Divider sx={{ mt: 3, mb: 1.5 }} />
                <Typography variant="caption" color="text.disabled" sx={{ textAlign: 'center', display: 'block' }}>
                    Metrics cached for 60 seconds.
                </Typography>
            </Box>
        </>
    );
}

Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] };
