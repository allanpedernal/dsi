import { Autocomplete, InputAdornment, TextField } from '@mui/material';
import { Business } from '@mui/icons-material';

export type CustomerOption = { id: number; label: string };

type Props = {
    options: CustomerOption[];
    value: number | null;
    onChange: (id: number | null) => void;
    /** When true, the selector is hidden (user is locked to their own customer). */
    locked?: boolean;
    sx?: object;
    label?: string;
};

/**
 * Customer-tenant picker shown to admin/manager to slice multi-tenant data.
 * Hidden for tenant-scoped users (customer role) since they can only see their own.
 */
export function CustomerFilter({ options, value, onChange, locked, sx, label = 'Customer' }: Props) {
    if (locked) return null;

    const selected = options.find((o) => o.id === value) ?? null;

    return (
        <Autocomplete
            size="small"
            sx={{ minWidth: 260, ...sx }}
            options={options}
            value={selected}
            getOptionLabel={(o) => o.label}
            isOptionEqualToValue={(a, b) => a.id === b.id}
            onChange={(_, v) => onChange(v?.id ?? null)}
            renderInput={(params) => {
                // MUI X Autocomplete v9 exposes params.slotProps.input — older shape was params.InputProps.
                // Pull whichever exists and extend it with our icon.
                const { InputProps, slotProps, ...rest } = params as unknown as Record<string, unknown> & {
                    InputProps?: Record<string, unknown> & { startAdornment?: React.ReactNode };
                    slotProps?: { input?: Record<string, unknown> & { startAdornment?: React.ReactNode } };
                };
                const inputSlot = slotProps?.input ?? InputProps ?? {};
                const existingStart = inputSlot.startAdornment;

                return (
                    <TextField
                        {...(rest as React.ComponentProps<typeof TextField>)}
                        label={label}
                        placeholder="All customers"
                        slotProps={{
                            ...(slotProps ?? {}),
                            input: {
                                ...inputSlot,
                                startAdornment: (
                                    <>
                                        <InputAdornment position="start"><Business fontSize="small" /></InputAdornment>
                                        {existingStart}
                                    </>
                                ),
                            },
                        }}
                    />
                );
            }}
        />
    );
}
