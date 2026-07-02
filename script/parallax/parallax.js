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
document.addEventListener("DOMContentLoaded", () => {
  AOS.init();
});
