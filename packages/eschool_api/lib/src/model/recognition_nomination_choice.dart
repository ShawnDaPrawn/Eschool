//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_named_reference.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_nomination_choice.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionNominationChoice {
  /// Returns a new [RecognitionNominationChoice] instance.
  RecognitionNominationChoice({
    required this.categoryId,

    required this.name,

    required this.opensOn,

    required this.closesOn,

    required this.status,

    required this.definitions,
  });

  // minimum: 1
  @JsonKey(name: r'category_id', required: true, includeIfNull: false)
  final int categoryId;

  @JsonKey(name: r'name', required: true, includeIfNull: false)
  final String name;

  @JsonKey(name: r'opens_on', required: true, includeIfNull: false)
  final String opensOn;

  @JsonKey(name: r'closes_on', required: true, includeIfNull: false)
  final String closesOn;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  @JsonKey(name: r'definitions', required: true, includeIfNull: false)
  final List<RecognitionNamedReference> definitions;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionNominationChoice &&
          other.categoryId == categoryId &&
          other.name == name &&
          other.opensOn == opensOn &&
          other.closesOn == closesOn &&
          other.status == status &&
          other.definitions == definitions;

  @override
  int get hashCode =>
      categoryId.hashCode +
      name.hashCode +
      opensOn.hashCode +
      closesOn.hashCode +
      status.hashCode +
      definitions.hashCode;

  factory RecognitionNominationChoice.fromJson(Map<String, dynamic> json) =>
      _$RecognitionNominationChoiceFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionNominationChoiceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
