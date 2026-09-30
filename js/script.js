/*
|--------------------------------------------------------------------------
| CholoGhuri - Main JavaScript
|--------------------------------------------------------------------------
*/

document.addEventListener("DOMContentLoaded", function () {

    /* =====================================================
       MOBILE NAVIGATION
    ===================================================== */

    const menuBtn = document.getElementById("menuBtn");
    const navLinks = document.getElementById("navLinks");

    if (menuBtn && navLinks) {

        menuBtn.addEventListener("click", function () {

            navLinks.classList.toggle("show");

            if (navLinks.classList.contains("show")) {
                menuBtn.innerHTML = "✕";
            } else {
                menuBtn.innerHTML = "☰";
            }

        });


        /* Close menu after clicking a link */

        navLinks.querySelectorAll("a").forEach(function (link) {

            link.addEventListener("click", function () {

                navLinks.classList.remove("show");

                menuBtn.innerHTML = "☰";

            });

        });

    }


    /* =====================================================
       PACKAGE SEARCH
    ===================================================== */

    const packageSearch = document.getElementById("packageSearch");
    const packageCards = document.querySelectorAll(".package-card");
    const noSearchResult = document.getElementById("noSearchResult");

    if (packageSearch && packageCards.length > 0) {

        packageSearch.addEventListener("input", function () {

            const searchText =
                packageSearch.value.toLowerCase().trim();

            let visibleCount = 0;

            packageCards.forEach(function (card) {

                const cardText =
                    card.textContent.toLowerCase();

                if (cardText.includes(searchText)) {

                    card.style.display = "";

                    visibleCount++;

                } else {

                    card.style.display = "none";

                }

            });


            if (noSearchResult) {

                if (visibleCount === 0) {

                    noSearchResult.style.display = "block";

                } else {

                    noSearchResult.style.display = "none";

                }

            }

        });

    }


    /* =====================================================
       PACKAGE FILTER
    ===================================================== */

    const destinationFilter =
        document.getElementById("destinationFilter");

    const priceFilter =
        document.getElementById("priceFilter");

    const durationFilter =
        document.getElementById("durationFilter");


    function applyPackageFilters() {

        if (packageCards.length === 0) {
            return;
        }

        const destination =
            destinationFilter
                ? destinationFilter.value.toLowerCase()
                : "";

        const price =
            priceFilter
                ? priceFilter.value
                : "";

        const duration =
            durationFilter
                ? durationFilter.value.toLowerCase()
                : "";


        let visibleCount = 0;


        packageCards.forEach(function (card) {

            const cardDestination =
                (card.dataset.destination || "").toLowerCase();

            const cardPrice =
                parseFloat(card.dataset.price || "0");

            const cardDuration =
                (card.dataset.duration || "").toLowerCase();


            let show = true;


            /* Destination */

            if (
                destination &&
                cardDestination !== destination
            ) {

                show = false;

            }


            /* Price */

            if (show && price) {

                if (price === "under5000" && cardPrice >= 5000) {
                    show = false;
                }

                if (
                    price === "5000-10000" &&
                    (cardPrice < 5000 || cardPrice > 10000)
                ) {
                    show = false;
                }

                if (price === "above10000" && cardPrice <= 10000) {
                    show = false;
                }

            }


            /* Duration */

            if (
                show &&
                duration &&
                !cardDuration.includes(duration)
            ) {

                show = false;

            }


            if (show) {

                card.style.display = "";

                visibleCount++;

            } else {

                card.style.display = "none";

            }

        });


        if (noSearchResult) {

            noSearchResult.style.display =
                visibleCount === 0
                    ? "block"
                    : "none";

        }

    }


    if (destinationFilter) {

        destinationFilter.addEventListener(
            "change",
            applyPackageFilters
        );

    }


    if (priceFilter) {

        priceFilter.addEventListener(
            "change",
            applyPackageFilters
        );

    }


    if (durationFilter) {

        durationFilter.addEventListener(
            "change",
            applyPackageFilters
        );

    }


    /* =====================================================
       RESET FILTERS
    ===================================================== */

    const resetFilters =
        document.getElementById("resetFilters");

    if (resetFilters) {

        resetFilters.addEventListener("click", function () {

            if (packageSearch) {
                packageSearch.value = "";
            }

            if (destinationFilter) {
                destinationFilter.value = "";
            }

            if (priceFilter) {
                priceFilter.value = "";
            }

            if (durationFilter) {
                durationFilter.value = "";
            }


            packageCards.forEach(function (card) {

                card.style.display = "";

            });


            if (noSearchResult) {
                noSearchResult.style.display = "none";
            }

        });

    }


    /* =====================================================
       BOOKING PEOPLE CALCULATION
    ===================================================== */

    const peopleInput =
        document.getElementById("people");

    const pricePerPerson =
        document.getElementById("pricePerPerson");

    const bookingTotal =
        document.getElementById("bookingTotal");


    function calculateBookingTotal() {

        if (
            !peopleInput ||
            !pricePerPerson ||
            !bookingTotal
        ) {
            return;
        }


        let people =
            parseInt(peopleInput.value) || 1;

        let price =
            parseFloat(pricePerPerson.value) || 0;


        if (people < 1) {
            people = 1;
            peopleInput.value = 1;
        }


        const total = people * price;


        bookingTotal.textContent =
            "৳ " + total.toLocaleString("en-BD");

    }


    if (peopleInput) {

        peopleInput.addEventListener(
            "input",
            calculateBookingTotal
        );

        peopleInput.addEventListener(
            "change",
            calculateBookingTotal
        );

    }


    calculateBookingTotal();


    /* =====================================================
       CONFIRM DELETE / CANCEL
    ===================================================== */

    const confirmButtons =
        document.querySelectorAll("[data-confirm]");


    confirmButtons.forEach(function (button) {

        button.addEventListener("click", function (event) {

            const message =
                button.dataset.confirm ||
                "Are you sure you want to continue?";


            if (!confirm(message)) {

                event.preventDefault();

            }

        });

    });


    /* =====================================================
       AUTO HIDE FLASH MESSAGE
    ===================================================== */

    const flashMessage =
        document.querySelector(".flash-message");


    if (flashMessage) {

        setTimeout(function () {

            flashMessage.style.opacity = "0";
            flashMessage.style.transition =
                "opacity 0.5s ease";


            setTimeout(function () {

                flashMessage.remove();

            }, 500);

        }, 4500);

    }


    /* =====================================================
       NUMBER INPUT VALIDATION
    ===================================================== */

    const numberInputs =
        document.querySelectorAll(
            'input[type="number"]'
        );


    numberInputs.forEach(function (input) {

        input.addEventListener("input", function () {

            const min =
                parseInt(input.getAttribute("min"));

            const max =
                parseInt(input.getAttribute("max"));

            let value =
                parseInt(input.value);


            if (!isNaN(min) && value < min) {

                input.value = min;

            }


            if (!isNaN(max) && value > max) {

                input.value = max;

            }

        });

    });


    /* =====================================================
       STAR RATING
    ===================================================== */

    const ratingStars =
        document.querySelectorAll(
            ".rating-input label"
        );

    const ratingInput =
        document.getElementById("rating");


    ratingStars.forEach(function (star) {

        star.addEventListener("click", function () {

            const value =
                star.dataset.rating;


            if (ratingInput) {

                ratingInput.value = value;

            }

        });

    });


    /* =====================================================
       PASSWORD SHOW / HIDE
    ===================================================== */

    const passwordToggles =
        document.querySelectorAll(
            ".password-toggle"
        );


    passwordToggles.forEach(function (toggle) {

        toggle.addEventListener("click", function () {

            const targetId =
                toggle.dataset.target;

            const passwordField =
                document.getElementById(targetId);


            if (!passwordField) {
                return;
            }


            if (passwordField.type === "password") {

                passwordField.type = "text";

                toggle.textContent = "Hide";

            } else {

                passwordField.type = "password";

                toggle.textContent = "Show";

            }

        });

    });


    /* =====================================================
       SMOOTH SCROLL
    ===================================================== */

    document.querySelectorAll(
        'a[href^="#"]'
    ).forEach(function (link) {

        link.addEventListener("click", function (event) {

            const targetId =
                link.getAttribute("href");


            if (
                targetId &&
                targetId !== "#"
            ) {

                const target =
                    document.querySelector(targetId);


                if (target) {

                    event.preventDefault();

                    target.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });

                }

            }

        });

    });

});
