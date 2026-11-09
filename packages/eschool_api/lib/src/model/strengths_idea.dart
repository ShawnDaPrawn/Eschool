//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'strengths_idea.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class StrengthsIdea {
  /// Returns a new [StrengthsIdea] instance.
  StrengthsIdea({
    required this.careerProfileId,

    required this.title,

    required this.summary,

    required this.because,

    required this.sourceTitle,

    required this.sourceUrl,
  });

  @JsonKey(name: r'career_profile_id', required: true, includeIfNull: false)
  final String careerProfileId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'summary', required: true, includeIfNull: false)
  final String summary;

  /// The learner's own topic or subject the career profile links.
  @JsonKey(name: r'because', required: true, includeIfNull: false)
  final String because;

  @JsonKey(name: r'source_title', required: true, includeIfNull: false)
  final String sourceTitle;

  @JsonKey(name: r'source_url', required: true, includeIfNull: false)
  final String sourceUrl;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is StrengthsIdea &&
          other.careerProfileId == careerProfileId &&
          other.title == title &&
          other.summary == summary &&
          other.because == because &&
          other.sourceTitle == sourceTitle &&
          other.sourceUrl == sourceUrl;

  @override
  int get hashCode =>
      careerProfileId.hashCode +
      title.hashCode +
      summary.hashCode +
      because.hashCode +
      sourceTitle.hashCode +
      sourceUrl.hashCode;

  factory StrengthsIdea.fromJson(Map<String, dynamic> json) =>
      _$StrengthsIdeaFromJson(json);

  Map<String, dynamic> toJson() => _$StrengthsIdeaToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
