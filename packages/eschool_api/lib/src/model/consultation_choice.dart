//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'consultation_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class ConsultationChoice {
  /// Returns a new [ConsultationChoice] instance.
  ConsultationChoice({required this.id, required this.name});

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is ConsultationChoice && other.id == id && other.name == name;

  @override
  int get hashCode => id.hashCode + name.hashCode;

  factory ConsultationChoice.fromJson(Map<String, dynamic> json) =>
      _$ConsultationChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$ConsultationChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
