import 'package:eschool_core/eschool_core.dart';

import 'recognition_repository.dart';

/// Socket metadata only invalidates protected readers; it never supplies moment text.
Stream<RecognitionInvalidation> recognitionLiveChanges(
  Stream<LiveInvalidation> events,
) => events
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
