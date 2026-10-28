// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'wallet_card_payment_review_response.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

WalletCardPaymentReviewResponse _$WalletCardPaymentReviewResponseFromJson(
  Map<String, dynamic> json,
) => $checkedCreate('WalletCardPaymentReviewResponse', json, ($checkedConvert) {
  $checkKeys(json, requiredKeys: const ['error', 'message', 'data', 'code']);
  final val = WalletCardPaymentReviewResponse(
    error: $checkedConvert('error', (v) => v as bool),
    message: $checkedConvert('message', (v) => v as String),
    data: $checkedConvert(
      'data',
      (v) => WalletCardPaymentReview.fromJson(v as Map<String, dynamic>),
    ),
    code: $checkedConvert('code', (v) => (v as num).toInt()),
  );
  return val;
});

Map<String, dynamic> _$WalletCardPaymentReviewResponseToJson(
  WalletCardPaymentReviewResponse instance,
) => <String, dynamic>{
  'error': instance.error,
  'message': instance.message,
  'data': instance.data.toJson(),
  'code': instance.code,
};
