//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/tutor_session_list_item.dart';
import 'package:json_annotation/json_annotation.dart';

part 'tutor_session_list.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class TutorSessionList {
  /// Returns a new [TutorSessionList] instance.
  TutorSessionList({
    required this.schoolId,

    required this.readerId,

    required this.learnerId,

    required this.page,

    required this.lastPage,

    required this.sessions,
  });

  // minimum: 1
  @JsonKey(name: r'school_id', required: true, includeIfNull: false)
  final int schoolId;

  // minimum: 1
  @JsonKey(name: r'reader_id', required: true, includeIfNull: false)
  final int readerId;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'page', required: true, includeIfNull: false)
  final int page;

  // minimum: 1
  @JsonKey(name: r'last_page', required: true, includeIfNull: false)
  final int lastPage;

  @JsonKey(name: r'sessions', required: true, includeIfNull: false)
  final List<TutorSessionListItem> sessions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is TutorSessionList &&
          other.schoolId == schoolId &&
          other.readerId == readerId &&
          other.learnerId == learnerId &&
          other.page == page &&
          other.lastPage == lastPage &&
          other.sessions == sessions;

  @override
  int get hashCode =>
      schoolId.hashCode +
      readerId.hashCode +
      learnerId.hashCode +
      page.hashCode +
      lastPage.hashCode +
      sessions.hashCode;

  factory TutorSessionList.fromJson(Map<String, dynamic> json) =>
      _$TutorSessionListFromJson(json);

  Map<String, dynamic> toJson() => _$TutorSessionListToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
