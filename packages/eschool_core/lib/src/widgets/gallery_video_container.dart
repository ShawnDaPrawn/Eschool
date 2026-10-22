import 'package:eschool_core/eschool_core.dart';
import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_animate/flutter_animate.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:youtube_player_iframe/youtube_player_iframe.dart';

class GalleryVideoContainer extends StatefulWidget {
  final String videoUrl;
  final VoidCallback? onPlayStateChanged;

  const GalleryVideoContainer({
    super.key,
    required this.videoUrl,
    this.onPlayStateChanged,
  });

  @override
  State<GalleryVideoContainer> createState() => GalleryVideoContainerState();
}

class GalleryVideoContainerState extends State<GalleryVideoContainer>
    with WidgetsBindingObserver {
  YoutubePlayerController? _controller;
  bool _isPlayerInitialized = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.paused ||
        state == AppLifecycleState.inactive) {
      _controller?.pauseVideo();
    }
  }

  static const _params = YoutubePlayerParams(
    mute: false,
    showControls: true,
    showFullscreenButton: true,
    enableCaption: false,
    strictRelatedVideos: true,
  );

  void _initializeController() {
    if (_isPlayerInitialized) return;

    final videoId = YoutubePlayerController.convertUrlToId(widget.videoUrl);
    if (videoId != null) {
      _controller = YoutubePlayerController.fromVideoId(
        videoId: videoId,
        autoPlay: true,
        params: _params,
      );
      // Load the player HTML into the WebView (public equivalent of init()).
      _controller!.initWithParams(params: _params);

      // Fullscreen button in YouTube's native controls fires FullscreenButtonPressed
      // via the JS bridge → controller.toggleFullScreen() → this listener.
      // Since the WebView is inline and can't expand to fill the screen, we
      // push a dedicated fullscreen route instead.
      _controller!.setFullScreenListener((isFullscreen) async {
        if (!mounted) return;
        if (isFullscreen) {
          // Capture current position before pausing
          final startSeconds = await _controller!.currentTime.catchError(
            (_) => 0.0,
          );
          _controller!.pauseVideo();

          await SystemChrome.setPreferredOrientations([
            DeviceOrientation.landscapeLeft,
            DeviceOrientation.landscapeRight,
          ]);
          await SystemChrome.setEnabledSystemUIMode(
            SystemUiMode.immersiveSticky,
          );

          if (!mounted) return;
          await Navigator.of(context, rootNavigator: true).push<void>(
            MaterialPageRoute(
              fullscreenDialog: true,
              builder: (_) => _FullscreenYoutubePlayer(
                videoUrl: widget.videoUrl,
                startSeconds: startSeconds,
              ),
            ),
          );

          if (!mounted) return;
          // Reset the Dart-side fullscreen flag on the inline controller so it
          // fires setFullScreenListener(false) → restores portrait below.
          _controller!.exitFullScreen();
        } else {
          SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
          SystemChrome.setEnabledSystemUIMode(
            SystemUiMode.manual,
            overlays: [SystemUiOverlay.top, SystemUiOverlay.bottom],
          );
        }
      });

      _controller!.stream.listen((value) {
        if (value.playerState == PlayerState.playing) {
          widget.onPlayStateChanged?.call();
        }
      });

      setState(() => _isPlayerInitialized = true);
    }
  }

  /// Called by parent to pause this video (e.g. when another video starts playing)
  void pauseVideo() {
    _controller?.pauseVideo();
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _controller?.close();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Animate(
      effects: customItemFadeAppearanceEffects(),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(12),
        child: SizedBox(
          width: double.maxFinite,
          height: double.maxFinite,
          child: !_isPlayerInitialized
              ? GestureDetector(
                  onTap: _initializeController,
                  child: _buildThumbnail(),
                )
              // Inline WebViewWidget — stays clipped to its container.
              // No OverlayPortal, so it never pops out over other widgets.
              : WebViewWidget(
                  controller: _controller!.webViewController,
                  gestureRecognizers:
                      const <Factory<OneSequenceGestureRecognizer>>{
                        Factory<OneSequenceGestureRecognizer>(
                          EagerGestureRecognizer.new,
                        ),
                      },
                ),
        ),
      ),
    );
  }

  Widget _buildThumbnail() {
    return Stack(
      fit: StackFit.expand,
      children: [
        CustomImageWidget(
          imagePath:
              SharedUiUtils.getYoutubeThumbnailUrl(widget.videoUrl) ?? '',
          boxFit: BoxFit.cover,
        ),
        ColoredBox(
          color: Theme.of(context).colorScheme.scrim.withValues(alpha: 0.38),
        ),
        Align(
          alignment: Alignment.center,
          child: Icon(
            Icons.play_circle,
            color: Theme.of(context).colorScheme.onInverseSurface,
            size: 50,
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// Fullscreen player route
// ---------------------------------------------------------------------------

/// A standalone fullscreen player route pushed when the user taps YouTube's
/// fullscreen button. Uses its own controller starting at [startSeconds] so
/// the WebView can fill the entire screen in landscape without needing to
/// move the inline platform view (which is not possible).
class _FullscreenYoutubePlayer extends StatefulWidget {
  const _FullscreenYoutubePlayer({
    required this.videoUrl,
    required this.startSeconds,
  });

  final String videoUrl;
  final double startSeconds;

  @override
  State<_FullscreenYoutubePlayer> createState() =>
      _FullscreenYoutubePlayerState();
}

class _FullscreenYoutubePlayerState extends State<_FullscreenYoutubePlayer> {
  late final YoutubePlayerController _controller;
  StreamSubscription<YoutubePlayerValue>? _stateSub;
  bool _buttonVisible = true;
  Timer? _hideTimer;
  PlayerState _lastPlayerState = PlayerState.unknown;

  static const _params = YoutubePlayerParams(
    mute: false,
    showControls: true,
    // Hide fullscreen button — we are already fullscreen.
    showFullscreenButton: false,
    enableCaption: false,
    strictRelatedVideos: true,
  );

  @override
  void initState() {
    super.initState();
    final videoId =
        YoutubePlayerController.convertUrlToId(widget.videoUrl) ?? '';
    _controller = YoutubePlayerController.fromVideoId(
      videoId: videoId,
      autoPlay: true,
      startSeconds: widget.startSeconds,
      params: _params,
    );
    _controller.initWithParams(params: _params);

    // Show our exit button whenever the player state changes — this mirrors
    // when YouTube reveals its own controls (play/pause/seek interactions).
    // We use stream listening instead of a GestureDetector overlay because
    // a GestureDetector.fill blocks taps from reaching the WebView platform view
    // even with HitTestBehavior.translucent.
    _stateSub = _controller.stream.listen((value) {
      if (!mounted) return;
      final newState = value.playerState;
      // Only react to meaningful state transitions (not continuous updates)
      if (newState != _lastPlayerState && newState != PlayerState.unknown) {
        _lastPlayerState = newState;
        _showButtonTemporarily();
      }
    });

    // Show the button on initial open, then auto-hide after 3 s
    _scheduleHide();
  }

  void _showButtonTemporarily() {
    setState(() => _buttonVisible = true);
    _scheduleHide();
  }

  void _scheduleHide() {
    _cancelHide();
    _hideTimer = Timer(const Duration(seconds: 3), () {
      if (mounted) setState(() => _buttonVisible = false);
    });
  }

  void _showControlsOnTap() {
    if (!mounted) return;
    _showButtonTemporarily();
  }

  void _cancelHide() => _hideTimer?.cancel();

  @override
  void dispose() {
    _cancelHide();
    _stateSub?.cancel();
    _controller.close();
    // Always restore portrait when leaving fullscreen.
    SystemChrome.setPreferredOrientations([DeviceOrientation.portraitUp]);
    SystemChrome.setEnabledSystemUIMode(
      SystemUiMode.manual,
      overlays: [SystemUiOverlay.top, SystemUiOverlay.bottom],
    );
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: true,
      child: Scaffold(
        backgroundColor: Theme.of(context).colorScheme.scrim,
        body: Listener(
          behavior: HitTestBehavior.translucent,
          onPointerDown: (_) => _showControlsOnTap(),
          child: Stack(
            children: [
              // Video fills the entire landscape screen — WebView receives all
              // touches directly; no overlay intercepts them.
              Center(
                child: AspectRatio(
                  aspectRatio: 16 / 9,
                  child: WebViewWidget(
                    controller: _controller.webViewController,
                    gestureRecognizers:
                        const <Factory<OneSequenceGestureRecognizer>>{
                          Factory<OneSequenceGestureRecognizer>(
                            EagerGestureRecognizer.new,
                          ),
                        },
                  ),
                ),
              ),
              // Exit fullscreen button — shown when player state changes
              // (matching YouTube's control show/hide cycle) and hidden after 3 s.
              PositionedDirectional(
                bottom: MediaQuery.of(context).padding.top + 8,
                end: 16,
                child: SafeArea(
                  child: AnimatedOpacity(
                    opacity: _buttonVisible ? 1.0 : 0.0,
                    duration: const Duration(milliseconds: 250),
                    child: IgnorePointer(
                      ignoring: !_buttonVisible,
                      child: Material(
                        color: Theme.of(
                          context,
                        ).colorScheme.scrim.withValues(alpha: 0.54),
                        borderRadius: BorderRadius.circular(20),
                        child: InkWell(
                          borderRadius: BorderRadius.circular(20),
                          onTap: () => Navigator.of(context).pop(),
                          child: Padding(
                            padding: const EdgeInsets.all(6),
                            child: Icon(
                              Icons.fullscreen_exit_rounded,
                              color: Theme.of(
                                context,
                              ).colorScheme.onInverseSurface,
                              size: 28,
                            ),
                          ),
                        ),
                      ),
                    ),
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
