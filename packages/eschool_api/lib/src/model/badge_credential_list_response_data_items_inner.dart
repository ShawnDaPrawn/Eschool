//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

// ignore_for_file: unused_element
import 'package:json_annotation/json_annotation.dart';

part 'badge_credential_list_response_data_items_inner.g.dart';

@JsonSerializable(
  checked: true,
  createToJson: true,
  disallowUnrecognizedKeys: false,
  explicitToJson: true,
)
class BadgeCredentialListResponseDataItemsInner {
  /// Returns a new [BadgeCredentialListResponseDataItemsInner] instance.
  BadgeCredentialListResponseDataItemsInner({
    required this.presentationId,

    required this.title,

    required this.presentedOn,

    required this.kind,

    required this.issued,
  });

  @JsonKey(name: r'presentation_id', required: true, includeIfNull: false)
  final int presentationId;

  @JsonKey(name: r'title', required: true, includeIfNull: false)
  final String title;

  @JsonKey(name: r'presented_on', required: true, includeIfNull: false)
  final DateTime presentedOn;

  @JsonKey(
    name: r'kind',
    required: true,
    includeIfNull: false,
    unknownEnumValue:
        BadgeCredentialListResponseDataItemsInnerKindEnum.unknownDefaultOpenApi,
  )
  final BadgeCredentialListResponseDataItemsInnerKindEnum kind;

  @JsonKey(name: r'issued', required: true, includeIfNull: false)
  final bool issued;

  @override
  bool operator ==(Object other) =>
      identical(this, other) ||
      other is BadgeCredentialListResponseDataItemsInner &&
          other.presentationId == presentationId &&
          other.title == title &&
          other.presentedOn == presentedOn &&
          other.kind == kind &&
          other.issued == issued;

  @override
  int get hashCode =>
      presentationId.hashCode +
      title.hashCode +
      presentedOn.hashCode +
      kind.hashCode +
      issued.hashCode;

  factory BadgeCredentialListResponseDataItemsInner.fromJson(
    Map<String, dynamic> json,
  ) => _$BadgeCredentialListResponseDataItemsInnerFromJson(json);

  Map<String, dynamic> toJson() =>
      _$BadgeCredentialListResponseDataItemsInnerToJson(this);

  @override
  String toString() {
    return toJson().toString();
  }
}

enum BadgeCredentialListResponseDataItemsInnerKindEnum {
  @JsonValue(r'award')
  award(r'award'),
  @JsonValue(r'badge')
  badge(r'badge'),
  @JsonValue(r'unknown_default_open_api')
  unknownDefaultOpenApi(r'unknown_default_open_api');

  const BadgeCredentialListResponseDataItemsInnerKindEnum(this.value);

  final String value;

  @override
  String toString() => value;
}
