import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Snackbar, Alert } from '@mui/material';
import { initEcho } from '@/echo';

type SaleNotification = {
    sale_id: number;
    reference: string;
    total: number;
    customer?: string;
    cashier?: string;
    message: string;
};

export function NotificationsToaster() {
    const page = usePage<{ auth: { user: { id: number } | null } }>();
    const userId = page.props.auth?.user?.id;
    const [open, setOpen] = useState(false);
    const [latest, setLatest] = useState<SaleNotification | null>(null);

    useEffect(() => {
        if (!userId) return;
        const echo = initEcho();
        if (!echo) return;
        const channel = echo.private(`App.Models.User.${userId}`);
        channel.notification((data: SaleNotification) => {
            setLatest(data);
            setOpen(true);
        });
        return () => {
            echo.leave(`App.Models.User.${userId}`);
        };
    }, [userId]);

    return (
        <Snackbar
            open={open}
            autoHideDuration={5000}
            onClose={() => setOpen(false)}
            anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
        >
            <Alert severity="success" onClose={() => setOpen(false)} variant="filled">
                {latest?.message ?? 'New notification'}
            </Alert>
        </Snackbar>
    );
}
