(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-mkt-video]').forEach(function (block) {
            var playBtn = block.querySelector('[data-mkt-video-play]');
            var videoModal = block.querySelector('[data-mkt-video-modal]');
            var closeModal = block.querySelector('[data-mkt-video-close]');
            var modalVideo = block.querySelector('[data-mkt-video-player]');

            if (!playBtn || !videoModal) {
                return;
            }

            function openVideo() {
                videoModal.classList.add('is-open');
                document.body.style.overflow = 'hidden';
                if (modalVideo) {
                    modalVideo.play().catch(function () {});
                }
            }

            function closeVideo() {
                videoModal.classList.remove('is-open');
                document.body.style.overflow = '';
                if (modalVideo) {
                    modalVideo.pause();
                    modalVideo.currentTime = 0;
                }
            }

            playBtn.addEventListener('click', openVideo);
            playBtn.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    openVideo();
                }
            });

            if (closeModal) {
                closeModal.addEventListener('click', closeVideo);
            }

            videoModal.addEventListener('click', function (event) {
                if (event.target === videoModal) {
                    closeVideo();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && videoModal.classList.contains('is-open')) {
                    closeVideo();
                }
            });
        });

        document.querySelectorAll('.mkt-home-video-player, [data-mkt-video-player]').forEach(function (video) {
            video.setAttribute('controlsList', 'nodownload noplaybackrate');
            video.setAttribute('disablePictureInPicture', '');
            video.addEventListener('contextmenu', function (event) {
                event.preventDefault();
            });
            video.addEventListener('dragstart', function (event) {
                event.preventDefault();
            });
        });
    });
})();
