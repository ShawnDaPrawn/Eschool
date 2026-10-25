//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'recognition_award_window.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class RecognitionAwardWindow {
  /// Returns a new [RecognitionAwardWindow] instance.
  RecognitionAwardWindow({
    required this.id,

    required this.awardCategoryId,

    required this.sessionYearId,

    required this.opensOn,

    required this.closesOn,

    required this.status,

    required this.revision,
  });

  // minimum: 1
  @JsonKey(name: r'id', required: true, includeIfNull: false)
  final int id;

  // minimum: 1
  @JsonKey(name: r'award_category_id', required: true, includeIfNull: false)
  final int awardCategoryId;

  // minimum: 1
  @JsonKey(name: r'session_year_id', required: true, includeIfNull: false)
  final int sessionYearId;

  @JsonKey(name: r'opens_on', required: true, includeIfNull: false)
  final String opensOn;

  @JsonKey(name: r'closes_on', required: true, includeIfNull: false)
  final String closesOn;

  @JsonKey(name: r'status', required: true, includeIfNull: false)
  final String status;

  // minimum: 1
  @JsonKey(name: r'revision', required: true, includeIfNull: false)
  final int revision;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is RecognitionAwardWindow &&
          other.id == id &&
          other.awardCategoryId == awardCategoryId &&
          other.sessionYearId == sessionYearId &&
          other.opensOn == opensOn &&
          other.closesOn == closesOn &&
          other.status == status &&
          other.revision == revision;

  @override
  int get hashCode =>
      id.hashCode +
      awardCategoryId.hashCode +
      sessionYearId.hashCode +
      opensOn.hashCode +
      closesOn.hashCode +
      status.hashCode +
      revision.hashCode;

  factory RecognitionAwardWindow.fromJson(Map<String, dynamic> json) =>
      _$RecognitionAwardWindowFromJson(json);

  Map<String, dynamic> toJson() => _$RecognitionAwardWindowToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
