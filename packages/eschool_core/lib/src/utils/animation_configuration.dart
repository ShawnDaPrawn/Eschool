import 'package:flutter/material.dart';
import 'package:flutter_animate/flutter_animate.dart';

//animations configuration, the same in both apps
//if this is false all item appearance animations will be turned off
const bool isApplicationItemAnimationOn = true;
//note: do not add Milliseconds values less then 10 as it'll result in errors
const int listItemAnimationDelayInMilliseconds = 100;
const int itemFadeAnimationDurationInMilliseconds = 250;
const int itemZoomAnimationDurationInMilliseconds = 200;
const int itemBounceScaleAnimationDurationInMilliseconds = 200;

/*
 change the effects to apply your own custom effects - only if you know about the flutter_animate package
*/
List<Effect<dynamic>> listItemAppearanceEffects({
  required int itemIndex,
  int totalLoadedItems = 15,
}) {
  return isApplicationItemAnimationOn
      ? [
          FadeEffect(
            delay: Duration(
              milliseconds:
                  (itemIndex % totalLoadedItems) *
                  (listItemAnimationDelayInMilliseconds - 9),
            ),
          ),
          SlideEffect(
            delay: Duration(
              milliseconds:
                  (itemIndex % totalLoadedItems) *
                  listItemAnimationDelayInMilliseconds,
            ),
            curve: Curves.easeInOut,
            end: SlideEffect.neutralValue.copyWith(dx: 0),
            begin: SlideEffect.neutralValue.copyWith(dx: -0.5),
          ),
        ]
      : [];
}

List<Effect<dynamic>> customItemFadeAppearanceEffects() {
  return isApplicationItemAnimationOn
      ? [
          const FadeEffect(
            duration: Duration(
              milliseconds: itemFadeAnimationDurationInMilliseconds,
            ),
          ),
        ]
      : [];
}

List<Effect<dynamic>> customItemZoomAppearanceEffects({
  Duration? delay,
  Duration? duration,
}) {
  return isApplicationItemAnimationOn
      ? [
          ScaleEffect(
            delay: delay,
            duration:
                duration ??
                const Duration(
                  milliseconds: itemZoomAnimationDurationInMilliseconds,
                ),
          ),
        ]
      : [];
}

List<Effect<dynamic>> customItemBounceScaleAppearanceEffects() {
  return isApplicationItemAnimationOn
      ? [
          const ScaleEffect(
            duration: Duration(
              milliseconds: itemBounceScaleAnimationDurationInMilliseconds,
            ),
            curve: Curves.bounceOut,
          ),
        ]
      : [];
}
