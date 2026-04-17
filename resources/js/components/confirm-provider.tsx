import { createContext, useCallback, useContext, useState, ReactNode } from 'react';
import {
    Button,
    Dialog,
    DialogActions,
    DialogContent,
    DialogContentText,
    DialogTitle,
    Stack,
} from '@mui/material';
import { WarningAmber } from '@mui/icons-material';

type ConfirmOptions = {
    title?: string;
    description?: ReactNode;
    confirmText?: string;
    cancelText?: string;
    tone?: 'primary' | 'error' | 'warning';
};

type ConfirmFn = (options: ConfirmOptions) => Promise<boolean>;

const ConfirmContext = createContext<ConfirmFn | null>(null);

export function useConfirm(): ConfirmFn {
    const ctx = useContext(ConfirmContext);
    if (!ctx) throw new Error('useConfirm must be used within <ConfirmProvider>');
    return ctx;
}

type PendingState = {
    options: ConfirmOptions;
    resolve: (value: boolean) => void;
} | null;

export function ConfirmProvider({ children }: { children: ReactNode }) {
    const [pending, setPending] = useState<PendingState>(null);

    const confirm = useCallback<ConfirmFn>((options) => {
        return new Promise<boolean>((resolve) => {
            setPending({ options, resolve });
        });
    }, []);

    const close = (value: boolean) => {
        pending?.resolve(value);
        setPending(null);
    };

    const tone = pending?.options.tone ?? 'error';

    return (
        <ConfirmContext.Provider value={confirm}>
            {children}
            <Dialog open={!!pending} onClose={() => close(false)} maxWidth="xs" fullWidth>
                <DialogTitle>
                    <Stack direction="row" spacing={1} alignItems="center">
                        <WarningAmber color={tone} />
                        <span>{pending?.options.title ?? 'Are you sure?'}</span>
                    </Stack>
                </DialogTitle>
                <DialogContent>
                    <DialogContentText>
                        {pending?.options.description ?? 'This action cannot be undone.'}
                    </DialogContentText>
                </DialogContent>
                <DialogActions>
                    <Button onClick={() => close(false)}>
                        {pending?.options.cancelText ?? 'Cancel'}
                    </Button>
                    <Button onClick={() => close(true)} variant="contained" color={tone} autoFocus>
                        {pending?.options.confirmText ?? 'Confirm'}
                    </Button>
                </DialogActions>
            </Dialog>
        </ConfirmContext.Provider>
    );
}
