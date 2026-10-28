import 'package:eschool_core/testing.dart';
import 'package:eschool_wallet/testing.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'smoke.dart' show pumpUntilFound, tapWhenReady;

Future<void> exerciseCommerce(
  WidgetTester tester,
  FakeApi api, {
  required int actor,
  bool learner = false,
}) async {
  final original = Map<String, Object>.of(api.routes);
  final server = CommerceFakeServer(api: api, actor: actor, learner: learner);
  await tester.tap(find.byTooltip('Refresh wallets'));
  await tester.pumpAndSettle();
  await tester.scrollUntilVisible(
    find.text('Shop and orders'),
    -200,
    scrollable: find
        .descendant(
          of: find.byKey(const ValueKey('wallet-screen-scroll')),
          matching: find.byType(Scrollable),
        )
        .first,
  );
  await tapWhenReady(tester, find.text('Shop and orders'));
  await tapWhenReady(tester, find.text('Synthetic tuckshop'));
  await pumpUntilFound(
    tester,
    find.byKey(const ValueKey('commerce-quantity-51')),
  );
  await tester.enterText(
    find.byKey(const ValueKey('commerce-quantity-51')),
    '1',
  );
  await tapWhenReady(tester, find.text('Review cart'));
  await pumpUntilFound(
    tester,
    find.text('Total from original wallet: ZAR 12.50'),
  );
  expect(server.writes, 0);
  await tapWhenReady(
    tester,
    find.text(
      'I reviewed the original wallet, items, quantities and total. Purchase once.',
    ),
  );
  await tapWhenReady(tester, find.text('Confirm quoted purchase'));
  await tapWhenReady(tester, find.text('VO-000001'));
  await pumpUntilFound(tester, find.byKey(const ValueKey('order-collection-qr-901')));
  expect(server.writes, 1);
  final reason = find.byWidgetPredicate(
    (w) =>
        w is TextField &&
        w.decoration?.labelText == 'Private order cancellation reason',
  );
  await tester.ensureVisible(reason);
  await tester.enterText(reason, 'Synthetic goods not yet collected');
  await tapWhenReady(
    tester,
    find.text(
      'Cancel only this uncollected order and return money to its original wallet.',
    ),
  );
  await tapWhenReady(tester, find.text('Cancel uncollected order'));
  await pumpUntilFound(tester, find.textContaining('Cancelled'));
  expect(server.cancellations, 1);
  expect(server.balances[learner ? 7 : 6], 5000);
  expect(find.byKey(const ValueKey('order-collection-qr-901')), findsNothing);
  server.allowed = false;
  await tester.tap(find.byTooltip('Refresh shop and orders'));
  await pumpUntilFound(
    tester,
    find.text('Your storefront access changed. Reopen Wallets.'),
  );
  expect(find.text('VO-000001'), findsNothing);
  expect(find.text('Synthetic sandwich'), findsNothing);
  api.routes
    ..clear()
    ..addAll(original);
  await tester.pageBack();
  await tester.pumpAndSettle();
}
