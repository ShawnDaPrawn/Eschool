import 'dart:async';

import 'package:flutter/material.dart';
import 'package:video_player/video_player.dart';
import 'package:youtube_player_iframe/youtube_player_iframe.dart';

class PlayPauseButtonContainer extends StatefulWidget {
  const PlayPauseButtonContainer({
    required this.isYoutubeVideo,
    required this.controlsAnimationController,
    super.key,
    this.youtubePlayerController,
    this.videoPlayerController,
  });
  final AnimationController controlsAnimationController;
  final bool isYoutubeVideo;
  final VideoPlayerController? videoPlayerController;
  final YoutubePlayerController? youtubePlayerController;

  @override
  State<PlayPauseButtonContainer> createState() =>
      _PlayPauseButtonContainerState();
}

class _PlayPauseButtonContainerState extends State<PlayPauseButtonContainer> {
  bool _isPlaying = false;
  bool _isCompleted = false;

  StreamSubscription<YoutubePlayerValue>? _youtubeSubscription;

  void _youtubeValueListener(YoutubePlayerValue value) {
    final isPlaying = value.playerState == PlayerState.playing;
    final isEnded = value.playerState == PlayerState.ended;

    if (mounted) {
      setState(() {
        _isPlaying = isPlaying;
        _isCompleted = isEnded;
      });
    }
  }

  void _videoListener() {
    _isPlaying = widget.videoPlayerController!.value.isPlaying;

    if (widget.videoPlayerController!.value.position.inSeconds != 0) {
      _isCompleted =
          widget.videoPlayerController!.value.position.inSeconds ==
          widget.videoPlayerController!.value.duration.inSeconds;
    }

    setState(() {});
  }

  @override
  void initState() {
    super.initState();
    _youtubeSubscription = widget.youtubePlayerController?.stream.listen(
      _youtubeValueListener,
    );
    widget.videoPlayerController?.addListener(_videoListener);
  }

  @override
  void dispose() {
    _youtubeSubscription?.cancel();
    widget.videoPlayerController?.removeListener(_videoListener);
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Center(
      child: IconButton(
        iconSize: 40,
        color: Theme.of(context).colorScheme.onInverseSurface,
        onPressed: () async {
          //if control menu is not opened then open the menu
          if (!widget.controlsAnimationController.isCompleted) {
            widget.controlsAnimationController.forward();
            return;
          }

          if (_isCompleted) {
            if (widget.isYoutubeVideo) {
              widget.youtubePlayerController!.seekTo(seconds: 0);
              widget.youtubePlayerController!.playVideo();
              return;
            } else {
              widget.videoPlayerController!.seekTo(Duration.zero);
              widget.videoPlayerController!.play();
              return;
            }
          }

          if (_isPlaying) {
            widget.isYoutubeVideo
                ? widget.youtubePlayerController!.pauseVideo()
                : widget.videoPlayerController!.pause();
            await Future.delayed(const Duration(milliseconds: 500));
            widget.controlsAnimationController.reverse();
          } else {
            widget.isYoutubeVideo
                ? widget.youtubePlayerController!.playVideo()
                : widget.videoPlayerController!.play();
            await Future.delayed(const Duration(milliseconds: 500));
            widget.controlsAnimationController.reverse();
          }
        },
        icon: _isCompleted
            ? const Icon(Icons.restart_alt)
            : _isPlaying
            ? const Icon(Icons.pause)
            : const Icon(Icons.play_arrow),
      ),
    );
  }
}
