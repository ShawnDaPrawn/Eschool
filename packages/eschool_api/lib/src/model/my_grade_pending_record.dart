//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'my_grade_pending_record.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class MyGradePendingRecord {
  /// Returns a new [MyGradePendingRecord] instance.
  MyGradePendingRecord({
    required this.id,

    required this.learnerId,

    required this.revision,

    required this.occurredOn,

    required this.status,

    required this.learnerName,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'learner_id', required: true, includeIfNull: false)
  final int learnerId;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'occurred_on', required: true, includeIfNull: false)
  final String occurredOn;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: MyGradePendingRecordStatusEnum.unknownDefaultOpenApi,
  )
  final MyGradePendingRecordStatusEnum status;

  @JsonKey(name: r'learner_name', required: true, includeIfNull: false)
  final String learnerName;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is MyGradePendingRecord &&
          other.id == id &&
          other.learnerId == learnerId &&
          other.revision == revision &&
          other.occurredOn == occurredOn &&
          other.status == status &&
          other.learnerName == learnerName;

  @override
  int get hashCode =>
      id.hashCode +
      learnerId.hashCode +
      revision.hashCode +
      occurredOn.hashCode +
      status.hashCode +
      learnerName.hashCode;

  factory MyGradePendingRecord.fromJson(Map<String, dynamic> json) =>
      _$MyGradePendingRecordFromJson(json);

  Map<String, dynamic> toJson() => _$MyGradePendingRecordToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum MyGradePendingRecordStatusEnum {
  @JsonValue(r'pending_review')
  pendingReview(r'pending_review'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const MyGradePendingRecordStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
