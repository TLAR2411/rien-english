import { api } from "@/utils/api";

/*
 * Forgot password (no login): API/app/Http/Controllers/Api/Auth/PasswordResetController.php
 * Sent with { silent: true }: the pages show their own messages, no pop-ups.
 */

/** Emails a reset link. Same reply for every email (it never tells if an account exists). */
export const sendResetLink = (email) => api.post("forgot-password", { email }, { silent: true });

/** Sets the new password with the token + email from the link in the email. */
export const resetPassword = (payload) => api.post("reset-password", payload, { silent: true });
