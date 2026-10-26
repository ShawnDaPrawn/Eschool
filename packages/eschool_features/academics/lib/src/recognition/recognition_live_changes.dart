import 'dart:async';
import 'package:eschool_core/eschool_core.dart';

import 'recognition_repository.dart';

/// Socket metadata only invalidates protected readers; it never supplies moment text.
Stream<RecognitionInvalidation> recognitionLiveChanges(
  Stream<LiveInvalidation> events, {
  Stream<void> refreshes = const Stream.empty(),
  int? schoolId,
}) {
  final changes = events
      .where(
        (event) =>
            event.type == 'house_points.changed' ||
            event.type == 'recognition.changed',
      )
      .map(
        (event) => RecognitionInvalidation(
          schoolId: event.schoolId,
          learnerId: event.learnerId,
        ),
      );
  return Stream.multi((controller) {
    final invalidations = changes.listen(
      controller.add,
      onError: controller.addError,
    );
    final reconnects = refreshes.listen((_) {
      if (schoolId != null)
        controller.add(RecognitionInvalidation(schoolId: schoolId));
    }, onError: controller.addError);
    controller.onCancel = () async {
      await invalidations.cancel();
      await reconnects.cancel();
    };
  }, isBroadcast: true);
}
