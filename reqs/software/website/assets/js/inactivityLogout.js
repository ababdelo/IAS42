document.addEventListener('DOMContentLoaded', () => {
    const inactivityLimit = 42 * 60 * 1000;
    const rememberMeEnabled = window.IAS42RememberMe === true;
    let timeoutId;
    let lastActivityTime = Date.now();

    if (rememberMeEnabled) {
        // console.log('Remember Me is enabled. Inactivity logout is disabled.');
        return;
    }

    const logout = () => {
        window.location.href = '/auth/logout';
    };

    const resetTimer = () => {
        lastActivityTime = Date.now();
        clearTimeout(timeoutId);
        timeoutId = setTimeout(logout, inactivityLimit);
    };

    /** Print remaining time to console every second for debugging purposes.
     */
   setInterval(() => {
       const elapsedTime = Date.now() - lastActivityTime;
       const remainingTime = Math.max(inactivityLimit - elapsedTime, 0);
       console.log(`Inactivity time remaining: ${Math.ceil(remainingTime / 1000)} seconds`);
   }, 1000);

    document.addEventListener('click', resetTimer);
    document.addEventListener('keydown', resetTimer);
    document.addEventListener('touchstart', resetTimer, { passive: true });
    document.addEventListener('scroll', resetTimer, { passive: true });

    resetTimer();
});
