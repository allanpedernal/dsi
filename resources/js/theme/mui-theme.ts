import { createTheme } from '@mui/material/styles';

export const buildMuiTheme = (mode: 'light' | 'dark' = 'light') =>
    createTheme({
        palette: {
            mode,
            primary: { main: mode === 'dark' ? '#a78bfa' : '#6366f1' },
            secondary: { main: '#22c55e' },
            background: {
                default: mode === 'dark' ? '#0a0a0a' : '#fafafa',
                paper: mode === 'dark' ? '#171717' : '#ffffff',
            },
        },
        shape: { borderRadius: 8 },
        typography: {
            fontFamily:
                '"Inter", "Instrument Sans", ui-sans-serif, system-ui, sans-serif',
        },
        components: {
            MuiButton: {
                defaultProps: { disableElevation: true },
                styleOverrides: { root: { textTransform: 'none', fontWeight: 500 } },
            },
            MuiPaper: { styleOverrides: { root: { backgroundImage: 'none' } } },
        },
    });
