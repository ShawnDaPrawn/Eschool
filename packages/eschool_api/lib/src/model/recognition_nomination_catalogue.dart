//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_nomination_choice.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_nomination_catalogue.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionNominationCatalogue {
  /// Returns a new [RecognitionNominationCatalogue] instance.
  RecognitionNominationCatalogue({required this.items});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<RecognitionNominationChoice> items;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionNominationCatalogue && other.items == items;

  @override
  int get hashCode => items.hashCode;

  factory RecognitionNominationCatalogue.fromJson(Map<String, dynamic> json) =>
      _$RecognitionNominationCatalogueFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionNominationCatalogueToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
