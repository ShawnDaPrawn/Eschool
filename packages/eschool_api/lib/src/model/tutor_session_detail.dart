//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_photo_receipt.dart';
import 'package:eschool_api/src/model/tutor_checkpoint_summary.dart';
import 'package:eschool_api/src/model/tutor_turn.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_session_detail.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorSessionDetail {
  /// Returns a new [TutorSessionDetail] instance.
  TutorSessionDetail({
    required this.schoolId,

    required this.readerId,

    required this.id,

    required this.learnerId,

    required this.subjectId,

    required this.groupId,

    required this.sessionYearId,

    required this.state,

    required this.page,

    required this.lastPage,

    required this.turns,

    required this.pendingPhotos,

    this.topicId,

    this.checkpoints,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final String id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'subject_id', required: true, includeIfNull: false)
  final int subjectId;

  // minimum: 1
  @JsonKey(name: r'group_id', required: true, includeIfNull: false)
  final int groupId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: TutorSessionDetailStateEnum.unknownDefaultOpenApi,
  )
  final TutorSessionDetailStateEnum state;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'turns', required: true, includeIfNull: false)
  final List<TutorTurn> turns;

  @JsonKey(name: r'pending_photos', required: true, includeIfNull: false)
  final List<TutorPhotoReceipt> pendingPhotos;

  // minimum: 1
  @JsonKey(name: r'topic_id', required: false, includeIfNull: false)
  final int? topicId;

  @JsonKey(name: r'checkpoints', required: false, includeIfNull: false)
  final List<TutorCheckpointSummary>? checkpoints;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorSessionDetail &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.subjectId == subjectId &&
          other.groupId == groupId &&
          other.sessionYearId == sessionYearId &&
          other.state == state &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.turns == turns &&
          other.pendingPhotos == pendingPhotos &&
          other.topicId == topicId &&
          other.checkpoints == checkpoints;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      id.hashCode +
      learnerId.hashCode +
      subjectId.hashCode +
      groupId.hashCode +
      sessionYearId.hashCode +
      state.hashCode +
      page.hashCode +
      lastPage.hashCode +
      turns.hashCode +
      pendingPhotos.hashCode +
      (topicId == null ? 0 : topicId.hashCode) +
      checkpoints.hashCode;

  factory TutorSessionDetail.fromJson(Map<String, dynamic> json) =>
      _$TutorSessionDetailFromJson(json);

  Map<String, dynamic> toJson() => _$TutorSessionDetailToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum TutorSessionDetailStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'closed')
  closed(r'closed'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const TutorSessionDetailStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
