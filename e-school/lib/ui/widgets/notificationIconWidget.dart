import 'package:eschool/app/routes.dart';
import 'package:eschool/ui/screens/home/homeScreen.dart';
import 'package:flutter/material.dart';

class NotificationIconWidget extends StatelessWidget {
  const NotificationIconWidget({super.key, this.size});
  final Size? size;

  @override
  Widget build(BuildContext context) {
    return ValueListenableBuilder(
      valueListenable: notificationCountValueNotifier,
      builder: (context, value, child) => Container(
        height: size?.height ?? 40,
        width: size?.width ?? 30,
        alignment: Alignment.centerRight,
        child: Stack(
          fit: StackFit.expand,
          children: [
            InkWell(
              onTap: () {
                Navigator.of(context).pushNamed(Routes.notifications);
              },
              child: Icon(
                Icons.notifications,
                color: Theme.of(context).colorScheme.onPrimary,
              ),
            ),
            if (value > 0)
              Positioned(
                top: size == null ? 0 : -5,
                right: 0,
                child: Container(
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Theme.of(context).colorScheme.error,
                  ),
                  padding: const EdgeInsets.all(4),
                  child: Text(
                    value > 9 ? '9+' : value.toString(),
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.onError,
                      fontSize: 9,
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
