/**
 * Molk Film — main.js
 * Handles: language toggle, seats-progress animation,
 * sticky enroll box, smooth scroll, mobile nav.
 */
( function () {
    'use strict';

    /* ── Language toggle (AR / EN) ─────────────────── */
    function initLangToggle() {
        var btn = document.getElementById( 'mf-lang-toggle' );
        if ( ! btn ) return;

        btn.addEventListener( 'click', function () {
            var current = document.documentElement.lang || 'ar';
            var next    = current === 'ar' ? 'en' : 'ar';

            // Polylang / WPML expose a URL; fall back to reload with ?lang=
            if ( window.MolkFilm && window.MolkFilm.langUrls && window.MolkFilm.langUrls[ next ] ) {
                window.location.href = window.MolkFilm.langUrls[ next ];
            } else {
                var url = new URL( window.location.href );
                url.searchParams.set( 'lang', next );
                window.location.href = url.toString();
            }
        } );
    }

    /* ── Animate seats progress bars ───────────────── */
    function initSeatsBars() {
        document.querySelectorAll( '.mf-seats-bar__fill' ).forEach( function ( bar ) {
            var target = parseFloat( bar.getAttribute( 'data-pct' ) ) || 0;
            bar.style.width = '0%';
            // Delay so animation plays after paint
            requestAnimationFrame( function () {
                setTimeout( function () {
                    bar.style.width = Math.min( target, 100 ) + '%';
                }, 120 );
            } );
        } );
    }

    /* ── Sticky enroll sidebar ──────────────────────── */
    function initStickyEnroll() {
        var box = document.querySelector( '.mf-enroll-box' );
        if ( ! box ) return;
        // CSS handles sticky; JS adds a class for shadow on scroll
        window.addEventListener( 'scroll', function () {
            if ( window.scrollY > 80 ) {
                box.classList.add( 'is-scrolled' );
            } else {
                box.classList.remove( 'is-scrolled' );
            }
        }, { passive: true } );
    }

    /* ── Smooth scroll for in-page anchors ─────────── */
    function initSmoothScroll() {
        document.querySelectorAll( 'a[href^="#"]' ).forEach( function ( a ) {
            a.addEventListener( 'click', function ( e ) {
                var target = document.querySelector( a.getAttribute( 'href' ) );
                if ( ! target ) return;
                e.preventDefault();
                target.scrollIntoView( { behavior: 'smooth', block: 'start' } );
            } );
        } );
    }

    /* ── Mobile nav toggle ──────────────────────────── */
    function initMobileNav() {
        var toggle = document.querySelector( '.mf-nav-toggle' );
        var nav    = document.querySelector( '.mf-nav-menu' );
        if ( ! toggle || ! nav ) return;

        toggle.addEventListener( 'click', function () {
            var open = nav.classList.toggle( 'is-open' );
            toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
        } );

        // Close on outside click
        document.addEventListener( 'click', function ( e ) {
            if ( ! nav.contains( e.target ) && ! toggle.contains( e.target ) ) {
                nav.classList.remove( 'is-open' );
                toggle.setAttribute( 'aria-expanded', 'false' );
            }
        } );
    }

    /* ── Enrollment CTA: disable when sold out ──────── */
    function initSoldOutState() {
        document.querySelectorAll( '.mf-enroll-box' ).forEach( function ( box ) {
            var taken = parseInt( box.getAttribute( 'data-seats-taken' ), 10 ) || 0;
            var total = parseInt( box.getAttribute( 'data-seats-total' ), 10 ) || 0;
            if ( total > 0 && taken >= total ) {
                var btn = box.querySelector( '.btn-enroll' );
                if ( btn ) {
                    btn.disabled = true;
                    btn.textContent = window.MolkFilm && window.MolkFilm.i18n
                        ? window.MolkFilm.i18n.soldOut
                        : 'المقاعد ممتلئة';
                    btn.classList.add( 'is-sold-out' );
                }
            }
        } );
    }

    /* ── Countdown timer (start_date) ──────────────── */
    function initCountdown() {
        document.querySelectorAll( '[data-countdown]' ).forEach( function ( el ) {
            var target = new Date( el.getAttribute( 'data-countdown' ) );
            if ( isNaN( target ) ) return;

            function tick() {
                var diff = target - Date.now();
                if ( diff <= 0 ) {
                    el.textContent = window.MolkFilm && window.MolkFilm.i18n
                        ? window.MolkFilm.i18n.started
                        : 'بدأ البرنامج';
                    return;
                }
                var d = Math.floor( diff / 86400000 );
                var h = Math.floor( ( diff % 86400000 ) / 3600000 );
                var m = Math.floor( ( diff % 3600000 ) / 60000 );
                var s = Math.floor( ( diff % 60000 ) / 1000 );
                el.textContent = d + 'ي ' + pad( h ) + ':' + pad( m ) + ':' + pad( s );
                setTimeout( tick, 1000 );
            }
            function pad( n ) { return n < 10 ? '0' + n : n; }
            tick();
        } );
    }

    /* ── Init all on DOM ready ──────────────────────── */
    document.addEventListener( 'DOMContentLoaded', function () {
        initLangToggle();
        initSeatsBars();
        initStickyEnroll();
        initSmoothScroll();
        initMobileNav();
        initSoldOutState();
        initCountdown();
    } );

} )();
