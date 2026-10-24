//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'school_subject_from_standard_write.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SchoolSubjectFromStandardWrite {
  /// Returns a new [SchoolSubjectFromStandardWrite] instance.
  SchoolSubjectFromStandardWrite({
    required this.standardId,

    required this.mediumId,

    required this.type,

    required this.bgColor,
  });

  // minimum: 1
  @JsonKey(name: r'standard_id', required: true, includeIfNull: false)
  final int standardId;

  // minimum: 1
  @JsonKey(name: r'medium_id', required: true, includeIfNull: false)
  final int mediumId;

  @JsonKey(
    name: r'type',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        SchoolSubjectFromStandardWriteTypeEnum.unknownDefaultOpenApi,
  )
  final SchoolSubjectFromStandardWriteTypeEnum type;

  @JsonKey(name: r'bg_color', required: true, includeIfNull: false)
  final String bgColor;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SchoolSubjectFromStandardWrite &&
          other.standardId == standardId &&
          other.mediumId == mediumId &&
          other.type == type &&
          other.bgColor == bgColor;

  @override
  int get hashCode =>
      standardId.hashCode +
      mediumId.hashCode +
      type.hashCode +
      bgColor.hashCode;

  factory SchoolSubjectFromStandardWrite.fromJson(Map<String, dynamic> json) =>
      _$SchoolSubjectFromStandardWriteFromJson(json);

  Map<String, dynamic> toJson() => _$SchoolSubjectFromStandardWriteToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum SchoolSubjectFromStandardWriteTypeEnum {
  @JsonValue(r'Theory')
  theory(r'Theory'),
  @JsonValue(r'Practical')
  practical(r'Practical'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const SchoolSubjectFromStandardWriteTypeEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
