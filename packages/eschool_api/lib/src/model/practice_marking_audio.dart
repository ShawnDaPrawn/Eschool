//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'practice_marking_audio.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class PracticeMarkingAudio {
  /// Returns a new [PracticeMarkingAudio] instance.
  PracticeMarkingAudio({
    required this.kept,

    required this.mime,

    required this.bytes,

    required this.deleteAfter,
  });

  @JsonKey(name: r'kept', required: true, includeIfNull: false)
  final bool kept;

  @JsonKey(name: r'mime', required: true, includeIfNull: false)
  final String mime;

  // minimum: 1
  @JsonKey(name: r'bytes', required: true, includeIfNull: false)
  final int bytes;

  @JsonKey(name: r'delete_after', required: true, includeIfNull: false)
  final String deleteAfter;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is PracticeMarkingAudio &&
          other.kept == kept &&
          other.mime == mime &&
          other.bytes == bytes &&
          other.deleteAfter == deleteAfter;

  @override
  int get hashCode =>
      kept.hashCode + mime.hashCode + bytes.hashCode + deleteAfter.hashCode;

  factory PracticeMarkingAudio.fromJson(Map<String, dynamic> json) =>
      _$PracticeMarkingAudioFromJson(json);

  Map<String, dynamic> toJson() => _$PracticeMarkingAudioToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
