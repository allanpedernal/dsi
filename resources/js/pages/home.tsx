import { Head } from '@inertiajs/react';
import { Box, Card, CardContent, Stack, Typography, Avatar } from '@mui/material';
import { WavingHand } from '@mui/icons-material';

export default function Home({ name }: { name: string | null }) {
    return (
        <>
            <Head title="Welcome" />
            <Box sx={{ p: 3, display: 'flex', justifyContent: 'center', alignItems: 'center', minHeight: '70vh' }}>
                <Card variant="outlined" sx={{ maxWidth: 560, width: '100%' }}>
                    <CardContent sx={{ p: 4 }}>
                        <Stack spacing={2} alignItems="center" textAlign="center">
                            <Avatar sx={{ bgcolor: 'primary.main', width: 64, height: 64 }}>
                                <WavingHand sx={{ fontSize: 32 }} />
                            </Avatar>
                            <Typography variant="h5" fontWeight={600}>
                                Welcome{name ? `, ${name}` : ''} 👋
                            </Typography>
                            <Typography variant="body1" color="text.secondary">
                                Your account has been created successfully. An administrator will grant you
                                the access you need shortly.
                            </Typography>
                            <Typography variant="body2" color="text.secondary">
                                Once permissions are assigned, you'll see new items appear in the sidebar.
                                You can manage your profile and password anytime from the settings menu.
                            </Typography>
                        </Stack>
                    </CardContent>
                </Card>
            </Box>
        </>
    );
}
