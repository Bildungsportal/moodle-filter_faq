/**
 * @copyright  2023 Austrian Federal Ministry of Education
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* global AOS */
define([], function() {
    return {
        init: function() {
            document.addEventListener("scroll", function() {
                const scrollTop = window.scrollY || document.documentElement.scrollTop;
                document.querySelectorAll(".parallax-bg").forEach((bg) => {
                    let speed = 0.5;
                    if (bg.classList.contains("bg-speed-1")) {
                        speed = 0.3;
                    } else if (bg.classList.contains("bg-speed-2")) {
                        speed = 0.5;
                    } else if (bg.classList.contains("bg-speed-13")) {
                        speed = 0.8;
                    }
                    const offsetTop = bg.parentElement.offsetTop;
                    const distance = scrollTop - offsetTop;
                    if (
                        !bg.classList.contains("bg-fixed") &&
                        distance > -window.innerHeight &&
                        distance < window.innerHeight * 2
                    ) {
                        bg.style.transform = `translateY(${distance * speed}px)`;
                    }
                });
            });

            if (typeof AOS !== 'undefined') {
                AOS.init();
            }
        }
    };
});
