//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/cover_source.dart';
import 'package:json_annotation/json_annotation.dart';

part 'cover_occurrence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class CoverOccurrence {
  /// Returns a new [CoverOccurrence] instance.
  CoverOccurrence({
    required this.id,

    required this.absenceId,

    required this.revision,

    required this.state,

    required this.onDate,

    required this.source_,

    required this.substituteTeacherId,

    required this.substituteName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'absence_id', required: true, includeIfNull: false)
  final int absenceId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(
    name: r'state',
    required: true,
    includeIfNull: false,
    unknownEnumValue: CoverOccurrenceStateEnum.unknownDefaultOpenApi,
  )
  final CoverOccurrenceStateEnum state;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'source', required: true, includeIfNull: false)
  final CoverSource source_;

  // minimum: 1
  @JsonKey(name: r'substitute_teacher_id', required: true, includeIfNull: true)
  final int? substituteTeacherId;

  @JsonKey(name: r'substitute_name', required: true, includeIfNull: true)
  final String? substituteName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is CoverOccurrence &&
          other.id == id &&
          other.absenceId == absenceId &&
          other.revision == revision &&
          other.state == state &&
          other.onDate == onDate &&
          other.source_ == source_ &&
          other.substituteTeacherId == substituteTeacherId &&
          other.substituteName == substituteName;

  @override
  int get hashCode =>
      id.hashCode +
      absenceId.hashCode +
      revision.hashCode +
      state.hashCode +
      onDate.hashCode +
      source_.hashCode +
      (substituteTeacherId == null ? 0 : substituteTeacherId.hashCode) +
      (substituteName == null ? 0 : substituteName.hashCode);

  factory CoverOccurrence.fromJson(Map<String, dynamic> json) =>
      _$CoverOccurrenceFromJson(json);

  Map<String, dynamic> toJson() => _$CoverOccurrenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum CoverOccurrenceStateEnum {
  @JsonValue(r'open')
  open(r'open'),
  @JsonValue(r'assigned')
  assigned(r'assigned'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'needs_review')
  needsReview(r'needs_review'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const CoverOccurrenceStateEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
