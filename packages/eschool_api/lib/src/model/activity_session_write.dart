//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'activity_session_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ActivitySessionWrite {
  /// Returns a new [ActivitySessionWrite] instance.
  ActivitySessionWrite({
    required this.operationId,

    required this.revision,

    required this.onDate,

    required this.name,

    required this.venue,

    required this.status,
  });

  @JsonKey(name: r'operation_id', required: true, includeIfNull: false)
  final String operationId;

  // minimum: 0
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @JsonKey(name: r'on_date', required: true, includeIfNull: false)
  final String onDate;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'venue', required: true, includeIfNull: false)
  final String venue;

  @JsonKey(
    name: r'status',
    required: true,
    includeIfNull: false,
    unknownEnumValue: ActivitySessionWriteStatusEnum.unknownDefaultOpenApi,
  )
  final ActivitySessionWriteStatusEnum status;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ActivitySessionWrite &&
          other.operationId == operationId &&
          other.revision == revision &&
          other.onDate == onDate &&
          other.name == name &&
          other.venue == venue &&
          other.status == status;

  @override
  int get hashCode =>
      operationId.hashCode +
      revision.hashCode +
      onDate.hashCode +
      name.hashCode +
      venue.hashCode +
      status.hashCode;

  factory ActivitySessionWrite.fromJson(Map<String, dynamic> json) =>
      _$ActivitySessionWriteFromJson(json);

  Map<String, dynamic> toJson() => _$ActivitySessionWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum ActivitySessionWriteStatusEnum {
  @JsonValue(r'scheduled')
  scheduled(r'scheduled'),
  @JsonValue(r'completed')
  completed(r'completed'),
  @JsonValue(r'cancelled')
  cancelled(r'cancelled'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const ActivitySessionWriteStatusEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
