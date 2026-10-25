//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_option.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationOption {
  /// Returns a new [ConsultationOption] instance.
  ConsultationOption({
    required this.id,

    required this.label,

    required this.statement,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'label', required: true, includeIfNull: false)
  final String label;

  @JsonKey(name: r'statement', required: true, includeIfNull: true)
  final String? statement;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationOption &&
          other.id == id &&
          other.label == label &&
          other.statement == statement;

  @override
  int get hashCode =>
      id.hashCode +
      label.hashCode +
      (statement == null ? 0 : statement.hashCode);

  factory ConsultationOption.fromJson(Map<String, dynamic> json) =>
      _$ConsultationOptionFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationOptionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
