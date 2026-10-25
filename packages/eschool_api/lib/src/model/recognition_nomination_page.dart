//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:eschool_api/src/model/recognition_nomination.dart';
import 'package:json_annotation/json_annotation.dart';

part 'recognition_nomination_page.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionNominationPage {
  /// Returns a new [RecognitionNominationPage] instance.
  RecognitionNominationPage({required this.items, required this.nextBefore});

  @JsonKey(name: r'items', required: true, includeIfNull: false)
  final List<RecognitionNomination> items;

  // minimum: 1
  @JsonKey(name: r'next_before', required: true, includeIfNull: true)
  final int? nextBefore;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionNominationPage &&
          other.items == items &&
          other.nextBefore == nextBefore;

  @override
  int get hashCode =>
      items.hashCode + (nextBefore == null ? 0 : nextBefore.hashCode);

  factory RecognitionNominationPage.fromJson(Map<String, dynamic> json) =>
      _$RecognitionNominationPageFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionNominationPageToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
