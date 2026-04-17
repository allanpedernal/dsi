import { ReactNode, useEffect, useState } from 'react';
import { ThemeProvider, CssBaseline } from '@mui/material';
import { LocalizationProvider } from '@mui/x-date-pickers/LocalizationProvider';
import { AdapterDayjs } from '@mui/x-date-pickers/AdapterDayjs';
import { buildMuiTheme } from '@/theme/mui-theme';
import { ConfirmProvider } from '@/components/confirm-provider';

export function MuiProvider({ children }: { children: ReactNode }) {
    const [mode, setMode] = useState<'light' | 'dark'>(() =>
        typeof document !== 'undefined' && document.documentElement.classList.contains('dark') ? 'dark' : 'light',
    );

    useEffect(() => {
        const observer = new MutationObserver(() => {
            setMode(document.documentElement.classList.contains('dark') ? 'dark' : 'light');
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
        return () => observer.disconnect();
    }, []);

    return (
        <ThemeProvider theme={buildMuiTheme(mode)}>
            <CssBaseline enableColorScheme />
            <LocalizationProvider dateAdapter={AdapterDayjs}>
                <ConfirmProvider>{children}</ConfirmProvider>
            </LocalizationProvider>
        </ThemeProvider>
    );
}
