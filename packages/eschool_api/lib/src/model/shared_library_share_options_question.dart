//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_share_options_question.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryShareOptionsQuestion {
  /// Returns a new [SharedLibraryShareOptionsQuestion] instance.
  SharedLibraryShareOptionsQuestion({required this.kind, required this.prompt});

  @JsonKey(name: r'kind', required: true, includeIfNull: false)
  final String kind;

  @JsonKey(name: r'prompt', required: true, includeIfNull: false)
  final String prompt;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryShareOptionsQuestion &&
          other.kind == kind &&
          other.prompt == prompt;

  @override
  int get hashCode => kind.hashCode + prompt.hashCode;

  factory SharedLibraryShareOptionsQuestion.fromJson(
    Map<String, dynamic> json,
  ) => _$SharedLibraryShareOptionsQuestionFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SharedLibraryShareOptionsQuestionToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
