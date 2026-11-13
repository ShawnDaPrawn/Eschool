//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'shared_library_item_summary_licence.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class SharedLibraryItemSummaryLicence {
  /// Returns a new [SharedLibraryItemSummaryLicence] instance.
  SharedLibraryItemSummaryLicence({required this.code, required this.url});

  @JsonKey(name: r'code', required: true, includeIfNull: false)
  final String code;

  @JsonKey(name: r'url', required: true, includeIfNull: true)
  final String? url;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is SharedLibraryItemSummaryLicence &&
          other.code == code &&
          other.url == url;

  @override
  int get hashCode => code.hashCode + (url == null ? 0 : url.hashCode);

  factory SharedLibraryItemSummaryLicence.fromJson(Map<String, dynamic> json) =>
      _$SharedLibraryItemSummaryLicenceFromJson(json);

  Map<String, dynamic> toJson() =>
      _$SharedLibraryItemSummaryLicenceToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}
