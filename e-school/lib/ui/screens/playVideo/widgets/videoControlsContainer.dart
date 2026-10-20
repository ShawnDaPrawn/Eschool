import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:video_player/video_player.dart';
import 'package:youtube_player_iframe/youtube_player_iframe.dart';

class VideoControlsContainer extends StatefulWidget {
  const VideoControlsContainer({
    required this.isYoutubeVideo,
    required this.controlsAnimationController,
    super.key,
    this.videoPlayerController,
    this.youtubePlayerController,
  });
  final bool isYoutubeVideo;
  final AnimationController controlsAnimationController;
  final YoutubePlayerController? youtubePlayerController;
  final VideoPlayerController? videoPlayerController;

  @override
  State<VideoControlsContainer> createState() => _VideoControlsContainerState();
}

class _VideoControlsContainerState extends State<VideoControlsContainer> {
  late Duration currentVideoDuration = Duration.zero;
  Duration _youtubeTotalDuration = Duration.zero;
  final double sliderHeight = 4;

  StreamSubscription<YoutubePlayerValue>? _youtubeValueSubscription;
  StreamSubscription<YoutubeVideoState>? _youtubePositionSubscription;

  void _youtubeValueListener(YoutubePlayerValue value) {
    if (mounted) {
      setState(() {
        _youtubeTotalDuration = value.metaData.duration;
      });
    }
  }

  void _youtubePositionListener(YoutubeVideoState state) {
    if (mounted) {
      setState(() {
        currentVideoDuration = state.position;
      });
    }
  }

  void _videoPlayerListener() {
    currentVideoDuration = widget.videoPlayerController!.value.position;
    setState(() {});
  }

  bool _allowGesture() {
    return widget.controlsAnimationController.isCompleted;
  }

  String _buildCurrentVideoDurationInHMS() {
    String time = '';
    if (currentVideoDuration.inHours != 0) {
      time = "${currentVideoDuration.inHours.toString().padLeft(2, '0')}:";
    }

    // Always show minutes, even if 0
    final int minutes = currentVideoDuration.inMinutes % 60;
    time = "$time${minutes.toString().padLeft(2, '0')}:";

    // Always show seconds
    final int seconds = currentVideoDuration.inSeconds % 60;
    time = "$time${seconds.toString().padLeft(2, '0')}";

    return time;
  }

  String _buildVideoDurationInHMS() {
    final Duration videoDuration = widget.isYoutubeVideo
        ? _youtubeTotalDuration
        : widget.videoPlayerController!.value.duration;

    String time = '';
    if (videoDuration.inHours != 0) {
      time = "${videoDuration.inHours.toString().padLeft(2, '0')}:";
    }

    // Always show minutes, even if 0
    final int minutes = videoDuration.inMinutes % 60;
    time = "$time${minutes.toString().padLeft(2, '0')}:";

    // Always show seconds
    final int seconds = videoDuration.inSeconds % 60;
    time = "$time${seconds.toString().padLeft(2, '0')}";

    return time;
  }

  double get _youtubeMaxSeconds => _youtubeTotalDuration.inSeconds.toDouble();

  @override
  void initState() {
    super.initState();
    _youtubeValueSubscription = widget.youtubePlayerController?.stream.listen(
      _youtubeValueListener,
    );
    _youtubePositionSubscription = widget
        .youtubePlayerController
        ?.videoStateStream
        .listen(_youtubePositionListener);
    widget.videoPlayerController?.addListener(_videoPlayerListener);
  }

  @override
  void dispose() {
    _youtubeValueSubscription?.cancel();
    _youtubePositionSubscription?.cancel();
    widget.videoPlayerController?.removeListener(_videoPlayerListener);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 15),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Padding(
            padding: const EdgeInsets.all(8),
            child: Row(
              children: [
                Text(
                  _buildCurrentVideoDurationInHMS().isEmpty
                      ? ''
                      : '${_buildCurrentVideoDurationInHMS()} / ${_buildVideoDurationInHMS()}',
                  style: const TextStyle(color: Colors.white),
                ),
                const Spacer(),
                IconButton(
                  color: Colors.white,
                  padding: EdgeInsets.zero,
                  onPressed: () {
                    if (_allowGesture()) {
                      if (MediaQuery.of(context).orientation ==
                          Orientation.portrait) {
                        SystemChrome.setPreferredOrientations([
                          DeviceOrientation.landscapeRight,
                          DeviceOrientation.landscapeLeft,
                        ]);
                        SystemChrome.setEnabledSystemUIMode(
                          SystemUiMode.immersiveSticky,
                        );
                      } else {
                        SystemChrome.setPreferredOrientations([
                          DeviceOrientation.portraitUp,
                        ]);
                        SystemChrome.setEnabledSystemUIMode(
                          SystemUiMode.manual,
                          overlays: [SystemUiOverlay.top, SystemUiOverlay.bottom],
                        );
                      }
                    } else {
                      //if control menu is not open then open here
                      widget.controlsAnimationController.forward();
                    }
                  },
                  icon: const Icon(Icons.fullscreen),
                ),
              ],
            ),
          ),
          SizedBox(
            height: sliderHeight,
            width: MediaQuery.sizeOf(context).width,
            child: SliderTheme(
              data: Theme.of(context).sliderTheme.copyWith(
                overlayShape: const RoundSliderOverlayShape(overlayRadius: 0),
                trackHeight: sliderHeight,
                trackShape: CustomTrackShape(),
                thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 7),
              ),
              child: Slider(
                max: widget.isYoutubeVideo
                    ? _youtubeMaxSeconds > 0
                          ? _youtubeMaxSeconds
                          : 1.0
                    : widget.videoPlayerController!.value.duration.inSeconds
                          .toDouble(),
                activeColor: Colors.white,
                inactiveColor: Colors.white38,
                value: widget.isYoutubeVideo
                    ? currentVideoDuration.inSeconds.toDouble().clamp(
                        0,
                        _youtubeMaxSeconds > 0 ? _youtubeMaxSeconds : 1.0,
                      )
                    : currentVideoDuration.inSeconds.toDouble(),
                thumbColor: Theme.of(context).colorScheme.primary,
                onChanged: (value) {
                  if (_allowGesture()) {
                    setState(() {
                      currentVideoDuration = Duration(seconds: value.toInt());
                    });
                    if (widget.isYoutubeVideo) {
                      widget.youtubePlayerController!.seekTo(
                        seconds: value,
                        allowSeekAhead: true,
                      );
                    } else {
                      widget.videoPlayerController!.seekTo(
                        currentVideoDuration,
                      );
                    }
                  } else {
                    //if control menu is not open then open here
                    widget.controlsAnimationController.forward();
                  }
                },
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class CustomTrackShape extends RectangularSliderTrackShape {
  @override
  Rect getPreferredRect({
    required RenderBox parentBox,
    required SliderThemeData sliderTheme,
    Offset offset = Offset.zero,
    bool isEnabled = false,
    bool isDiscrete = false,
  }) {
    return Offset(offset.dx, offset.dy) &
        Size(parentBox.size.width, sliderTheme.trackHeight!);
  }
}
