{{--
    Picks the theme before first paint (so a dark page never flashes light) and exposes it to the
    app as window.PostItTheme. Inline and plain JS on purpose: the Vite bundle loads too late.

    Modes: 'clock' — dark at night by the browser's local time; 'browser' — follows the OS/browser
    setting; 'manual' — the user's own choice. In the first two, the navbar button sets a temporary
    override that expires at the next automatic switch (clock) or after 12 hours / when the system
    setting changes (browser), and the mode takes over again.
--}}
<script>
    (function () {
        var NIGHT_FROM = 20;
        var NIGHT_UNTIL = 7;
        var BROWSER_OVERRIDE_MS = 12 * 60 * 60 * 1000;
        var KEY = 'theme-override';

        var root = document.documentElement;
        var media = window.matchMedia('(prefers-color-scheme: dark)');
        var state = { mode: root.dataset.themeMode || 'browser', manualDark: root.dataset.themeManual === 'dark' };
        var timer = null;

        function isNight(date) {
            var hour = date.getHours();
            return hour >= NIGHT_FROM || hour < NIGHT_UNTIL;
        }

        function nextClockSwitch(now) {
            var next = new Date(now);
            next.setHours(isNight(now) ? NIGHT_UNTIL : NIGHT_FROM, 0, 0, 0);
            if (next <= now) next.setDate(next.getDate() + 1);
            return next.getTime();
        }

        // Storage can be unavailable (private mode, blocked site data): then there is simply no override.
        function readOverride() {
            try {
                var override = JSON.parse(localStorage.getItem(KEY));
                if (override && override.until > Date.now()) return override;
                localStorage.removeItem(KEY);
            } catch (e) {}
            return null;
        }

        function writeOverride(override) {
            try {
                if (override) localStorage.setItem(KEY, JSON.stringify(override));
                else localStorage.removeItem(KEY);
            } catch (e) {}
        }

        function modeDark() {
            if (state.mode === 'manual') return state.manualDark;
            if (state.mode === 'clock') return isNight(new Date());
            return media.matches;
        }

        function isDark() {
            var override = state.mode !== 'manual' && readOverride();
            return override ? override.dark : modeDark();
        }

        // Re-checks at the next moment the theme can change on its own: a clock switch or an override expiring.
        function schedule() {
            clearTimeout(timer);
            var times = [];
            if (state.mode === 'clock') times.push(nextClockSwitch(new Date()));
            var override = state.mode !== 'manual' && readOverride();
            if (override) times.push(override.until);
            if (times.length) timer = setTimeout(apply, Math.max(0, Math.min.apply(null, times) - Date.now()) + 1000);
        }

        function apply() {
            var dark = isDark();
            root.dataset.theme = dark ? 'dark' : 'light';
            window.dispatchEvent(new CustomEvent('themechange', { detail: { dark: dark } }));
            schedule();
        }

        function toggle() {
            var dark = !isDark();
            if (state.mode === 'manual') {
                state.manualDark = dark;
            } else if (dark === modeDark()) {
                // Toggled back to what the mode shows anyway: nothing left to override.
                writeOverride(null);
            } else {
                writeOverride({ dark: dark, until: state.mode === 'clock' ? nextClockSwitch(new Date()) : Date.now() + BROWSER_OVERRIDE_MS });
            }
            apply();
            return dark;
        }

        media.addEventListener('change', function () {
            if (state.mode === 'browser') writeOverride(null);
            apply();
        });
        // Timers don't run while the device sleeps; catch up when the tab is seen again.
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) apply();
        });

        window.PostItTheme = {
            isDark: isDark,
            toggle: toggle,
            mode: function () {
                return state.mode;
            },
            // Called by app.js after every visit with the user's saved settings.
            configure: function (mode, manualDark) {
                if (mode === state.mode && manualDark === state.manualDark) return;
                // A new mode starts clean: an override made under the old one no longer applies.
                if (mode !== state.mode) writeOverride(null);
                state.mode = mode;
                state.manualDark = manualDark;
                apply();
            },
        };

        apply();
    })();
</script>
