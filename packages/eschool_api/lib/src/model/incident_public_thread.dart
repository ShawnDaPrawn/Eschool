//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'incident_public_thread.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class IncidentPublicThread {
  /// Returns a new [IncidentPublicThread] instance.
  IncidentPublicThread({
    required this.author,

    required this.body,

    required this.createdAt,
  });

  @JsonKey(
    name: r'author',
    required: true,
    includeIfNull: false,
    unknownEnumValue: IncidentPublicThreadAuthorEnum.unknownDefaultOpenApi,
  )
  final IncidentPublicThreadAuthorEnum author;

  @JsonKey(name: r'body', required: true, includeIfNull: false)
  final String body;

  @JsonKey(name: r'created_at', required: true, includeIfNull: true)
  final DateTime? createdAt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is IncidentPublicThread &&
          other.author == author &&
          other.body == body &&
          other.createdAt == createdAt;

  @override
  int get hashCode =>
      author.hashCode +
      body.hashCode +
      (createdAt == null ? 0 : createdAt.hashCode);

  factory IncidentPublicThread.fromJson(Map<String, dynamic> json) =>
      _$IncidentPublicThreadFromJson(json);

  Map<String, dynamic> toJson() => _$IncidentPublicThreadToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum IncidentPublicThreadAuthorEnum {
  @JsonValue(r'reporter')
  reporter(r'reporter'),
  @JsonValue(r'school')
  school(r'school'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const IncidentPublicThreadAuthorEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
